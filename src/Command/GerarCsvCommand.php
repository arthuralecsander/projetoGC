<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Smalot\PdfParser\Parser;

class GerarCsvCommand extends Command
{
    protected static $defaultName = 'app:gerar-csv';

    protected function configure(): void
    {
        $this
            ->setDescription('Gerador de CSV, filtra o PDF de concursados e pega apenas os aprovados, separados por PCD e PSD')
            ->setHelp('Caso nao rode, confira os path do csv e do pdf');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {   
        //Inicia o Logger
        $logger = new ConsoleLogger($output);

        //Inicio dos arquivos PDF e CSV
        $pdfFile = 'src\Command\ED_6__2019__DPDF_DEFENSOR_RES_PROVISORIO_OBJETIVA.pdf'; 
        $csv = 'src\Command\csvFiltrado.csv';

        //Faz a leitura do PDF
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfFile);

        //Abertura do arquivo csv
        $fp = fopen($csv,'w');
        ftruncate($fp,0); //Limpa o CSV

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

        $count = 0;
        //Loop para registrar as PCD no CSV
        for($i = 0; count($arrayPCD) > $i; $i++){
            $arrayPCD[$i] = $arrayPCD[$i] . ", Sim";
            $count++;
            print_r( $count . " Encontrado : " . $arrayPCD[$i] . "\n");
            fputcsv($fp, explode(",",$arrayPCD[$i]));
            
        }
        //Loop para registrar as PSD no CSV
        for($i = 0; count($arrayPSD) > $i; $i++){
            $arrayPSD[$i] = $arrayPSD[$i] . ", Nao";
            $count++;
            print_r( $count . " Encontrado : " . $arrayPSD[$i] . "\n");
            fputcsv($fp, explode(",",$arrayPSD[$i]));
            
        }
        //Fecha o CSV
        print_r("CSV Gerado com Sucesso!");
        fclose($fp);
        return Command::SUCCESS;   
    }
}