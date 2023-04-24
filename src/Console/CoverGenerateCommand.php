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
 * @copyright   Copyright (c) 2023, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Pdf\Console;

use Opus\Pdf\Cover\DefaultCoverGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function basename;
use function copy;
use function getcwd;

use const DIRECTORY_SEPARATOR;

/**
 * Console command to generate a PDF cover for a given document ID.
 */
class CoverGenerateCommand extends Command
{
    /**
     * Argument for the name of the output file containing the generated PDF cover
     */
    public const ARGUMENT_DOC_ID = 'DocID';

    /**
     * Option for the name of the output file containing the generated PDF cover
     */
    public const OPTION_OUTPUT_FILE = 'out';

    /**
     * Option for the path to the cover template to be used for PDF cover generation
     */
    public const OPTION_TEMPLATE_PATH = 'template';

    protected function configure()
    {
        parent::configure();

        $help = <<<EOT
The <fg=green>cover:generate</> command can be used to generate a PDF cover for a single document.

If no <fg=green>out</> file (*.pdf) is given the output file name will be "DOCUMENT_ID.pdf".

If no <fg=green>template</> path is provided or the given path doesn't exist, the default template that's
appropriate for the specified document will be used.

Note that files with a PDF cover that get downloaded via the frontdoor are cached and only get
updated if the file's document is changed. This command will instead always force a rebuild of
the cover sheet which can be useful when developing a custom cover template.
EOT;

        $this->setName('cover:generate')
            ->setDescription('Generates a PDF cover for a document')
            ->setHelp($help)
            ->addArgument(
                self::ARGUMENT_DOC_ID,
                InputArgument::REQUIRED,
                'ID of document'
            )
            ->addOption(
                self::OPTION_OUTPUT_FILE,
                'o',
                InputOption::VALUE_OPTIONAL,
                'Name of output file'
            )
            ->addOption(
                self::OPTION_TEMPLATE_PATH,
                't',
                InputOption::VALUE_OPTIONAL,
                'Path to cover template'
            );
    }

    /**
     * Executes this command to generate a PDF cover.
     *
     * @return int 0 in case of success, otherwise an exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO Support other PDF generator implementations

        $docId        = $input->getArgument(self::ARGUMENT_DOC_ID);
        $outputName   = $input->getOption(self::OPTION_OUTPUT_FILE);

        // TODO '--template' option should support specifying just the name of a template like in the configuration
        //      for collections. It should not be necessary to specify full paths, although this can be supported
        //      additionally.
        $templatePath = $input->getOption(self::OPTION_TEMPLATE_PATH);

        $coverGenerator = new DefaultCoverGenerator();
        $coverPath      = $coverGenerator->processDocumentId($docId, $templatePath);
        if ($coverPath === null) {
            return Command::FAILURE;
        }

        // TODO Output file should be generated directly
        $coverName  = basename($coverPath);
        $outputPath = getcwd() . DIRECTORY_SEPARATOR . ($outputName ?? $coverName);
        copy($coverPath, $outputPath);

        $output->writeln('Generated cover for document with ID ' . $docId . ' at ' . $outputPath);

        return Command::SUCCESS;
    }
}
