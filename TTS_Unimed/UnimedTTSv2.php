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


//variaveis de texto
$imut_audiosDir = '/var/lib/asterisk/agi-bin/TTS_Unimed/imut_audios/';
$etapa_inicialv1 = 'etapa_inicialv1.wav';
/*Olá tudo bem? Eu preciso falar com..*/

$etapa_inicialv2 = 'etapa_inicialv2.wav';
/*É você?*/

$digite_novamente = 'digite_novamente.wav';
/*Desculpe, não consegui confirmar a informação,
poderia digitar novamente?
*/

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
    $agi->verbose("Gerado arquivo de áudio temporário " . $alawFile . " e seu arquivo auxiliar " . $alawFile_aux);
    return $alawFile;
}

// Função para enviar dados para API externa
function sendToApi($data, $url = 'http://192.168.100.116:5000/webhook') {
    $ch = curl_init();
    
    // Configuração da requisição cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($data))
    ));

    // Executa a requisição e obtém a resposta
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Verifica se houve erro na requisição
    if(curl_errno($ch)) {
        $error_message = curl_error($ch);
        curl_close($ch);
        throw new Exception('Erro ao enviar requisição para API: ' . $error_message);
    }
    
    // Verifica o código de resposta HTTP
    if ($httpcode >= 400) {
        curl_close($ch);
        throw new Exception('Erro na API: Código ' . $httpcode);
    }
    
    curl_close($ch);
    
    return $response;
}


try {
    // Etapa 0: Texto inicial para interação com o usuário
    $cliente = "Lucas";
    $id = "991447700";
    $texto = $cliente;
    //$state = "chamada_atendida"; // Exemplo de estado para confirmação
    //$data = array(
    //    'id' => $id,
    //    'state' => $state
    //);
    //sendToApi($data); // Enviar estado para a API
    
    $alawFile = mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
    $agi->exec("Playback", $imut_audiosDir . $etapa_inicialv1);
    $agi->exec("Playback", $alawFile);
    $agi->exec("Playback", $imut_audiosDir . $etapa_inicialv2);
    //delAudio($alawFile, $agi); Removido pois poderá ser reaproveitado na opcao 2
    
    $max_attempts = 3;
    $attempt = 0;
    while ($attempt < $max_attempts) {
        $dtmf = $agi->get_data('beep', 10000, 1)['result']; // Captura a entrada do usuário
        if ($dtmf === '1') {
            include_once 'etapaConfirmacao.php'; // Incluir arquivo da etapa de confirmação
            $state = "cliente_confirmou";
            // $data = array(
            //     'id' => $id,
            //     'state' => $state
            // );
            // sendToApi($data); // Enviar estado para a API
            delAudio($alawFile, $agi);
            EtapaConfirmacao::handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id); // Encaminhando para classe de confirmação.
            break;
        } elseif ($dtmf === '2') {
            $agi->verbose("Usuário digitou '2' para Não.");
            $state = "cliente_negou";
            // $data = array(
            //     'id' => $id,
            //     'state' => $state
            // );
            // sendToApi($data); // Enviar estado para a API
            include_once 'etapaNegacao.php'; // Incluir arquivo da etapa de negação
            EtapaNegacao::handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $cliente, $alawFile); // Encaminhando para classe de negação.
            break;
        } elseif (empty($dtmf)) {
            $agi->verbose("Usuário não respondeu.");
            break;
        } else {
            $agi->verbose("Resposta inválida: $dtmf");
            if ($attempt < $max_attempts - 1) {
                $agi->exec("Playback", $imut_audiosDir . $digite_novamente); // Solicita que o usuário tente novamente
            }
        }
        $attempt++;
    }
    
    // Verifica se o loop terminou sem resposta válida e avisa
    if ($attempt == $max_attempts) {
        $agi->verbose("Máximo de tentativas atingido sem resposta válida. Avisando API.");
        // $state = "tentativa_sem_resposta";
        // $data = array(
        //     'id' => $id,
        //     'state' => $state
        // );
       //  sendToApi($data); // Enviar estado para a API
    }

} catch (Exception $e) {
    $agi->verbose("Erro ao sintetizar ou converter áudio: " . $e->getMessage());
}

// Finalizar a chamada AGI
$agi->hangup();
?>
