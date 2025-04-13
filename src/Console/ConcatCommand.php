<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @copyright   Copyright (c) 2025, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Pdf\Console;

use Opus\Pdf\Cover\DefaultCoverGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function file_exists;

/**
 * Console command to generate a PDF cover for a given document ID.
 */
class ConcatCommand extends Command
{
    public const ARGUMENT_FILES = 'files';

    protected function configure()
    {
        parent::configure();

        $help = <<<EOT
Merges two PDF files.
EOT;

        $this->setName('pdf:concat')
            ->setDescription('Generates a PDF cover for a document')
            ->setHelp($help)
            ->addArgument(
                self::ARGUMENT_FILES,
                InputArgument::IS_ARRAY,
                '[coverPDF] [docPDF] [outputPDF]'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $input->getArgument(self::ARGUMENT_FILES);

        if (count($files) !== 3) {
            $output->writeln('<error>Too few arguments</error>');
            return Command::FAILURE;
        }

        $coverPdf = $files[0];
        if (! file_exists($coverPdf)) {
            $output->writeln("<error>PDF file not found: {$coverPdf}</error>");
        }

        $documentPdf = $files[1];
        if (! file_exists($documentPdf)) {
            $output->writeln("<error>PDF file not found: {$documentPdf}</error>");
        }

        $outputPdf = $files[2];

        $coverGenerator = new DefaultCoverGenerator();
        $concatenator   = $coverGenerator->getPdfConcatenator();

        if ($concatenator !== null) {
            if ($concatenator->join($coverPdf, $documentPdf, $outputPdf) === null) {
                $output->writeln("<error>Failed to merge PDF files</error>");
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
