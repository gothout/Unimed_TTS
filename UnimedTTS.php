#!/usr/bin/php -q
<?php
/* *********************************************
 * @Programmer: Lucas Daniel Chaves
 * @Date: 2024-07-12
 * @Description: Script AGI para vocalização e captura de DTMF
 ********************************************* */

// Incluir as classes necessárias
require_once 'IBMWatsonTextToSpeech.php'; // Sua classe IBMWatsonTextToSpeech
require_once 'AudioConverter.php'; // Classe para conversão de áudio
require_once('/var/lib/asterisk/agi-bin/phpagi.php'); // Classe AGI

// Configurações da IBM Watson TTS
$apiKey = "3BSntQGR_4zEjkh6WqOZqIlpe9T6xTi-iuwzJXXb5IDq";
$baseUrl = "https://api.us-south.text-to-speech.watson.cloud.ibm.com/instances/0f310545-b3e4-44af-9407-c954cd25b03f";
$voice = 'pt-BR_IsabelaV3Voice';

// Diretório de trabalho para salvar arquivos temporários
$work_dir = '/var/lib/asterisk/agi-bin/TTS_Unimed/temp_audios';

// Instanciar a classe AGI
$agi = new AGI();

// Instanciar a classe IBMWatsonTextToSpeech
$ibmWatson = new IBMWatsonTextToSpeech($apiKey, $baseUrl, $work_dir);

// Instanciar a classe AudioConverter
$converter = new AudioConverter();

function delAudio($alawFile, $agi) {
    unlink($alawFile);
    $alawFile_aux = $alawFile . ".alaw";
    unlink($alawFile_aux);
    $agi->verbose("Removido arquivo temporario: " . $alawFile . " e seu arquivo auxiliar " . $alawFile_aux);
}
function mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter) {
    $outputFile = $ibmWatson->synthesizeAudio($texto, $voice, $id);
    $alawFile = $converter->convertToAlaw($outputFile, $work_dir, $id);
    $agi->exec("Playback", $alawFile);
    $agi->verbose("Gerado arquivo de aúdio temporario " . $alawFile . " e seu arquivo auxiliar " . $alawFile_aux);
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
    
    // Etapa confirmação Passo 1
    if ($dtmf === '1') {
        $agi->verbose("Usuário digitou '1' para Sim.");
        $texto = "Sou a Anne da Unimed
                    Blumenau, informo que esta
                    ligação está sendo gravada
                    sob o número de protocolo
                    $id,
                    caso queira ouvir
                    novamente digite um";
        $alawFile = mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
        delAudio($alawFile, $agi);
        $result = $agi->get_data('beep', 10000, 1);
        $dtmf = $result['result'];
        if ($dtmf === '1') {
            $agi->verbose("Usuario digitou 1 para escutar.");
            $texto = "Você digitou 1! Encerrando a chamada.";
            $outputFile = $ibmWatson->synthesizeAudio($texto, $voice, $id);
            $alawFile = $converter->convertToAlaw($outputFile, $work_dir, $id);
            $agi->exec("Playback", $alawFile);

            $agi->hangup();
        }
    } elseif ($dtmf === '2' || empty($dtmf)) {
        $agi->verbose("Usuário digitou '2' para Não ou não respondeu.");
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
