---
# 
# Markdown template for PDF cover generation
# ------------------------------------------
# 
# Created for: Institut für Auslandsbeziehungen e.V. (ifa), https://www.ifa.de
# 
# REQUIREMENTS:
# - This template requires the packages `pandoc` (>= v2.11) and `texlive-xetex` to be installed.
#   
# - If not commented out (see `sansfont:` & `mainfont:` below), this template requires the
#   "Open Sans" (true type or open type) font to be installed. This font is available under
#   the Apache License v.2.0 at <https://fonts.google.com/specimen/Open+Sans>.
#   
# NOTES:
# - This template can be converted to a cover PDF using Pandoc and XeTeX via two steps:
#   
#   - `pandoc /PATH/TO/TEMPLATE_DIR/cover-template.md /PATH/TO/metadata.yaml --wrap=preserve --bibliography=/PATH/TO/metadata.yaml --template=/PATH/TO/TEMPLATE_DIR/cover-template.md --variable=images-basepath:/PATH/TO/TEMPLATE_DIR/ > cover.md`
#   - `pandoc cover.md --resource-path=/PATH/TO/TEMPLATE_DIR/ --bibliography=/PATH/TO/metadata.yaml --citeproc --pdf-engine=xelatex --pdf-engine-opt=-output-driver="xdvipdfmx -V 3 -z 0" -o cover.pdf`
#   
# - As in the examples above, this template requires two calls to pandoc with the following arguments:
#   - `--bibliography=` set to the path of the metadata file containing the document's bibliographic metadata
#   - `--template=` set to the path of this template file
#   - `--variable=` set to `images-basepath:` and followed by the base path of the `images` subdirectory containing images used by this template
#   - `--resource-path=` set to the base path of the `styles` subdirectory containing the citation style used by this template
#   - `--citeproc` which causes a formatted citation to be generated from the bibliographic metadata
#   - `--pdf-engine=` set to `xelatex` which specifies that XeTeX will be used to generate the PDF (allowing the template to use Unicode & system fonts)
#   - `--pdf-engine-opt=` set to `-output-driver="xdvipdfmx -V 3 -z 0"` which specifies to use PDF version 1.3 without compression
#        - NOTE: since this option seems to cause a Pandoc exception when passed thru PHP code, we use below `\special{dvipdfmx:config ...}` includes instead
# 
documentclass: scrartcl # KOMA-Script class for articles
papersize: a4
pagestyle: empty # don't print page numbers in the footer
sansfont: "Open Sans"
mainfont: "Open Sans"
# fontsize: 10pt # KOMA-Script default: 11pt
number-sections: false # specifies whether numbers should be printed in front of headings
citation-style: styles/apa.csl # specifies the citation style; we use APA here, default is Chicago Manual of Style author-date
# bibliography: metadata.yaml # specifies the external bibliography; supported formats: BibLaTeX (.bib), BibTeX (.bibtex), CSL JSON (.json), CSL YAML (.yaml)
nocite: |
  @*
colorlinks: true # specifies whether links should be colored 
urlcolor: "blue" # specifies the link color
graphics: true # specifies whether images should be supported (if true, this will insert `\usepackage{graphicx}` in the header includes)
header-includes: |
  \special{dvipdfmx:config V 3}
  \special{dvipdfmx:config z 0}
  \usepackage{scrlayer-scrpage}
  \lohead{
    \subsection{ifa Repository}
  }
  \cohead{}
  \rohead{
    \subsection{\hfill Open Access Repository}
    \begin{minipage}[b]{165pt}
    \rightline{\small \url{https://opustest.bsz-bw.de/ifa}}
    \end{minipage}
  }
  \lofoot{
    \subsubsection{Nutzungsbedingungen}
    \begin{minipage}[t][27mm][t]{165pt}
    \tiny Dieser Text wird unter einer CC BY-NC-ND Lizenz \newline
    (Namensnennung – Nicht-kommerziell – Keine Bearbeitungen) \newline
    zur Verfügung gestellt. Nähere Auskünfte dazu finden Sie hier: \newline
    \url{https://creativecommons.org/licenses/by-nc-nd/4.0/deed.de}
    \end{minipage}
  }
  \cofoot{
    \begin{minipage}[t][27mm][c]{34mm}
    \rightline{\includegraphics[width=27mm]{$images-basepath$images/by-nc-nd.png}}
    \end{minipage}
  }
  \rofoot{
    \subsubsection{\hfill Terms of use}
    \begin{minipage}[t][27mm][t]{142pt}
    \tiny This document is made available under a CC BY-NC-ND \newline
    licence (Attribution – NonCommercial – NoDerivatives). \newline
    For more information see: \newline
    \url{https://creativecommons.org/licenses/by-nc-nd/4.0}
    \end{minipage}
  }
---

$--REPOSITORY LOGOS
\rightline{\small \emph{in partnership with}}


\begin{figure}
\begin{minipage}[c]{0.37\linewidth}
{\centering 
\includegraphics[width=50mm]{$images-basepath$images/logo.pdf}
}
\end{minipage}%
%
\begin{minipage}[c]{0.40\linewidth}
{\centering 
~
}
\end{minipage}%
%
\begin{minipage}[c]{0.23\linewidth}
{\centering 
\includegraphics[width=30mm]{$images-basepath$images/partner_logo.png}
}
\end{minipage}%
\end{figure}


$--DOCUMENT METADATA

$if(title)$
# $title$
$endif$


$if(author-meta)$
### $author-meta$
$endif$


$if(bibliography)$
\small Suggested citation: \newline \tiny

::: {#refs}
\small 
:::
$endif$


$if(abstract)$
### Abstract

\small $abstract$
$endif$