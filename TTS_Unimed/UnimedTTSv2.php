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
//require_once '/var/lib/asterisk/agi-bin/TTS_Unimed/apiUnimed/getDados.php'; // Classe para obter dados da API Unimed.


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
//$getDados = new GetDados();
$ibmWatson = new IBMWatsonTextToSpeech($apiKey, $baseUrl, $work_dir);
$converter = new AudioConverter();

//receber numero
$nrTelefone = $agi->request['agi_callerid'];
$agi->verbose("Número do Caller ID: " . $nrTelefone);

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


try {
    // Etapa 0: Texto inicial para interação com o usuário
    $cliente = "Lucas";
    $nrTelefone = "991447700";
    $id = $nrTelefone;
    $nrProtocolo = $nrTelefone;
    $nrCarteira = nrTelefone;
    $texto = $cliente;
    $dtNasc = "18072002";
    
    $nrCPF = "11671213920";
    $nrCNPJ = "81385593000153";
    $idDocumento = $nrCPF;

    // Verifica se o $idDocumento tem mais de 11 dígitos
    if (strlen($idDocumento) > 11) {
        // Fluxo normal para CNPJ
        $agi->verbose("Seguindo fluxo de CNPJ para: $nrTelefone");
        $alawFile = mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
        $agi->exec("Playback", $imut_audiosDir . $etapa_inicialv1);
        $agi->exec("Playback", $alawFile);
        $agi->exec("Playback", $imut_audiosDir . $etapa_inicialv2);

        $max_attempts = 3;
        $attempt = 0;
        while ($attempt < $max_attempts) {
            $dtmf = $agi->get_data('beep', 10000, 1)['result']; // Captura a entrada do usuário
            if ($dtmf === '1') {
                include_once 'etapaConfirmacao_CNPJ.php'; // Incluir arquivo da etapa de confirmação
                delAudio($alawFile, $agi);
                EtapaConfirmacao_CNPJ::handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $nrProtocolo, $nrCNPJ); // Encaminhando para classe de confirmação.
                break;
            } elseif ($dtmf === '2') {
                $agi->verbose("Usuário digitou '2' para Não.");
                include_once 'etapaNegacao_CNPJ.php'; // Incluir arquivo da etapa de negação
                EtapaNegacao_CNPJ::handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $cliente, $alawFile, $nrProtocolo); // Encaminhando para classe de negação.
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
        }
    } else {
        // Fluxo normal para CPF
        $agi->verbose("Seguindo fluxo de CPF para: $nrTelefone");
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
                delAudio($alawFile, $agi);
                EtapaConfirmacao::handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $nrProtocolo, $dtNasc, $nrCPF); // Encaminhando para classe de confirmação.
                break;
            } elseif ($dtmf === '2') {
                $agi->verbose("Usuário digitou '2' para Não.");
                $state = "cliente_negou";
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
        }
    }

} catch (Exception $e) {
    $agi->verbose("Erro ao sintetizar ou converter áudio: " . $e->getMessage());
}

// Finalizar a chamada AGI
$agi->hangup();
?>
