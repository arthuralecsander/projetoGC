<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Smalot\PdfParser\Parser;

class GerarCsvCommand extends Command
{
    protected static $defaultName = 'app:gerar-csv';

    protected function configure(): void
    {
        $this
            ->setDescription('')
            ->setHelp('');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {   
        //Inicio dos arquivos PDF e CSV
        $pdfFile = 'src\Command\ED_6__2019__DPDF_DEFENSOR_RES_PROVISORIO_OBJETIVA.pdf'; 
        $csv = 'src\Command\csvFiltrado.csv';

        //Faz a leitura do PDF
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfFile);

        //Abertura do arquivo csv
        $fp = fopen($csv,'w');

        //Remove as quebras de linha do PDF 
        $pdfText = str_replace(chr(194) . chr(160), " ", $pdf->getText());
        $pdfText = preg_replace("/\r|\n|[\s]+/", " ", $pdfText); 

        //Regex de busca N de inscricao, Nome, Qnt Acerto, Nota
        $re = "/[0-9]{8}\,\s*[a-zA-Z\s]+\,\s*[0-9]+\,\s*[0-9]+\.[0-9]+/mi";

        //Divide o Texto em PSD (antes) 1.1.1 (depois) PCD
        $pos = strpos($pdfText, '1.1.1');
        $arrayPCD = array();
        $arrayPSD = array();

        //Seleciona o Texto da parte das PCD
        $textPCD = substr($pdfText,$pos); 
        //Utiliza o regex para encontrar todas PCD
        preg_match_all($re, $textPCD, $matches);
        $arrayPCD = $matches[0];

         //Seleciona o Texto da parte das PSD
        $textPSD = substr($pdfText, 0, $pos);
        //Utiliza o regex para encontrar todas PSD
        preg_match_all($re, $textPSD, $matches); 
        $arrayPSD = $matches[0];

        //Insere o cabecalho do CSV
        fputcsv($fp, array('Numero de inscricao','Nome do candidato','Numero de acertos','Nota provisoria','PCD'));

        //Loop para registrar as PCD no CSV
        for($i = 0; count($arrayPCD) > $i; $i++){
            $arrayPCD[$i] = $arrayPCD[$i] . ", Sim";
            fputcsv($fp, explode(",",$arrayPCD[$i]));
            
        }
        //Loop para registrar as PSD no CSV
        for($i = 0; count($arrayPSD) > $i; $i++){
            $arrayPSD[$i] = $arrayPSD[$i] . ", Nao";
            fputcsv($fp, explode(",",$arrayPSD[$i]));
            
        }
        //Fecha o CSV
        fclose($fp);
        return Command::SUCCESS;   
    }
}
