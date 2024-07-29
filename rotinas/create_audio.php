#!/usr/bin/php -q
<?php
/* *********************************************
 * @Programador: Lucas Daniel Chaves
 * @Data: 2024-07-12
 * @Descrição: Script AGI para vocalização textos
 ********************************************* */

// Classes necessarias
require_once 'IBMWatsonTextToSpeech.php'; // Classe para conversão na IBM Watson.
require_once 'AudioConverter.php'; // Classe para converter o áudio para Alaw.
// Paramentros para classe IBMWatson
$apiKey = "3BSntQGR_4zEjkh6WqOZqIlpe9T6xTi-iuwzJXXb5IDq";
$baseUrl = "https://api.us-south.text-to-speech.watson.cloud.ibm.com/instances/0f310545-b3e4-44af-9407-c954cd25b03f";
$voice = 'pt-BR_IsabelaV3Voice';


// Diretorio para salvar os arquivos temporarios
$work_dir = '/var/lib/asterisk/agi-bin/TTS_Unimed/rotina'; // Alterado para diretório /teste

$ibmWatson = new IBMWatsonTextToSpeech($apiKey, $baseUrl, $work_dir);
$converter = new AudioConverter();


function mkAudio($texto, $voice, $id, $work_dir, $ibmWatson, $converter) {
    $outputFile = $ibmWatson->synthesizeAudio($texto, $voice, $id);
    $alawFile = $converter->convertToAlaw($outputFile, $work_dir, $id);
    return $alawFile;
}

try {
    $id = 'quest_voceconhecev1_cnpj';
    $texto = "Você conhece o
responsável financeiro pela";
    $alawFile = mkAudio($texto, $voice, $id, $work_dir, $ibmWatson, $converter);

} catch (Exception $e) {
    echo "Erro ao sintetizar ou converter áudio: " . $e->getMessage()   ;
}

?>
