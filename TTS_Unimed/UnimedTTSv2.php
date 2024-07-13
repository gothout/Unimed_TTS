#!/usr/bin/php -q
<?php
/* *********************************************
 * @Programador: Lucas Daniel Chaves
 * @Data: 2024-07-12
 * @Descrição: Script AGI para vocalização e captura de DTMF
 ********************************************* */

// Classes necessarias
require_once 'IBMWatsonTextToSpeech.php'; // Classe para conversão na IBM Watson.
require_once 'AudioConverter.php'; // Classe para converter o áudio para Alaw.
require_once '/var/lib/asterisk/agi-bin/phpagi.php'; // Classe AGI.
require_once 'etapaConfirmacao.php'; // Classe para etapa de confirmação.

// Paramentros para classe IBMWatson
$apiKey = "3BSntQGR_4zEjkh6WqOZqIlpe9T6xTi-iuwzJXXb5IDq";
$baseUrl = "https://api.us-south.text-to-speech.watson.cloud.ibm.com/instances/0f310545-b3e4-44af-9407-c954cd25b03f";
$voice = 'pt-BR_IsabelaV3Voice';

// Diretorio para salvar os arquivos temporarios
$work_dir = '/var/lib/asterisk/agi-bin/TTS_Unimed/temp_audios';

// Instanciar classes necessarias
$agi = new AGI();
$ibmWatson = new IBMWatsonTextToSpeech($apiKey, $baseUrl, $work_dir);
$converter = new AudioConverter();


//Funçoes para reaproveitamento de criação de audio.
function delAudio($alawFile, $agi) {
    unlink($alawFile);
    $alawFile_aux = $alawFile . ".alaw";
    unlink($alawFile_aux);
    $agi->verbose("Removido arquivo temporário: " . $alawFile . " e seu arquivo auxiliar " . $alawFile_aux);
}
function mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter) {
    $outputFile = $ibmWatson->synthesizeAudio($texto, $voice, $id);
    $alawFile = $converter->convertToAlaw($outputFile, $work_dir, $id);
    $agi->exec("Backgroud", $alawFile);
    $agi->verbose("Gerado arquivo de áudio temporário " . $alawFile . " e seu arquivo auxiliar " . $alawFile_aux);
    return $alawFile; // retorna para remoção na função delAudio
}

try {
    // Etapa 0: Texto inicial para interação com o usuário
    $cliente = "Lucas";
    $texto = "Olá, tudo bem? Eu preciso falar com $cliente, é você? Digite '1 para Sim' e '2 para Não'";
    $id = "991447700";

    $alawFile = mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
    delAudio($alawFile, $agi);

    // Aguardar a resposta DTMF do usuário
    $result = $agi->get_data('beep', 10000, 1); // Captura DTMF com timeout de 10 segundos
    $dtmf = $result['result'];

    if ($dtmf === '1') {
        include_once 'etapaConfirmacao.php'; // Incluir arquivo da etapa de confirmação
        EtapaConfirmacao::handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id); //Encaminhando para classe de confirmação.
    } elseif ($dtmf === '2' || empty($dtmf)) {
        $agi->verbose("Usuário digitou '2' para Não ou não respondeu.");
        // Lógica para lidar com resposta '2' ou sem resposta
    } else {
        $agi->verbose("Resposta inválida: $dtmf");
        // Lógica para lidar com resposta inválida
    }

} catch (Exception $e) {
    $agi->verbose("Erro ao sintetizar ou converter áudio: " . $e->getMessage());
}

// Finalizar a chamada AGI
$agi->hangup();
?>