#!/usr/bin/php -q
<?php
/* *********************************************
 * @Programador: Lucas Daniel Chaves
 * @Data: 2024-07-12
 * @Descrição: Script AGI para URA TTS
 ********************************************* */

// Classes necessarias
require_once 'IBMWatsonTextToSpeech.php'; // Classe para conversão na IBM Watson.
require_once 'AudioConverter.php'; // Classe para converter o áudio para Alaw.
require_once '/var/lib/asterisk/agi-bin/phpagi.php'; // Classe AGI.
require_once 'getNome.php'; // Classe para obter o nome do cliente.
require_once '/var/lib/asterisk/agi-bin/TTS_Unimed/apiUnimed/getDados.php'; //buscar dados de clientes
require_once '/var/lib/asterisk/agi-bin/TTS_Unimed/apiUnimed/putUnimed.php'; //envio de put para api da Unimed

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
$nrEtapa = "0";

// Diretorio para salvar os arquivos temporarios
$work_dir = '/var/lib/asterisk/agi-bin/TTS_Unimed/temp_audios';

// Instanciar classes necessarias
$agi = new AGI();
$ibmWatson = new IBMWatsonTextToSpeech($apiKey, $baseUrl, $work_dir);
$converter = new AudioConverter();
$nomeFetcher = new ClienteNomeFetcher();
$getDados = new GetDados();
$putUnimedAPI = new PutUnimed();
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
    $agi->verbose("Gerado arquivo de áudio temporário " . $alawFile);
    return $alawFile;
}
try {
    $cliente = $nomeFetcher->getNomeCliente($nrTelefone);
    $agi->verbose("Nome do cliente: " . $cliente);

    $data = $getDados->getData($nrTelefone, $cliente);

    if (!is_array($data)) {
        throw new Exception("Dados do cliente não encontrados ou formato inválido.");
    }

    $clienteData = $data['Data'][0];
    $idDocumento = isset($clienteData['NR_CPF']) ? $clienteData['NR_CPF'] : (isset($clienteData['NR_CNPJ']) ? $clienteData['NR_CNPJ'] : '');
    $dtNasc = isset($clienteData['DT_NASC']) ? date("dmY", strtotime($clienteData['DT_NASC'])) : '';
    $nrProtocolo = isset($clienteData['NR_PROTOCOLO']) ? $clienteData['NR_PROTOCOLO'] : '';
    $nrCarteirinha = isset($clienteData['NR_CARTEIRINHA']) ? str_replace(['.', '-'], '', $clienteData['NR_CARTEIRINHA']) : '';
    $qtdFaturas = count($data['Data']) - 1;

    $agi->verbose("ID Documento (CPF ou CNPJ): " . $idDocumento);
    $agi->verbose("DT_NASC: " . $dtNasc);
    $agi->verbose("NR_PROTOCOLO: " . $nrProtocolo);
    $agi->verbose("NR_CARTEIRINHA: " . $nrCarteirinha);
    $agi->verbose("QTD_FATURAS: " . $qtdFaturas);

    $nrTitulo = [];
    $nrSaldoTitulo = [];
    $nrDiasAtraso = [];

    for ($i = 1; $i <= $qtdFaturas; $i++) {
        $fatura = $data['Data'][$i];
        $nrTitulo[] = $fatura['NR_TITULO'];
        $nrSaldoTitulo[] = $fatura['VL_SALDO_TITULO'];
        $nrDiasAtraso[] = $fatura['DT_DIAS_ATRASO'];
        $agi->verbose("NR_TITULO[$i]: " . $nrTitulo[$i - 1]);
        $agi->verbose("VL_SALDO_TITULO[$i]: " . $nrSaldoTitulo[$i - 1]);
        $agi->verbose("DT_DIAS_ATRASO[$i]: " . $nrDiasAtraso[$i - 1]);
    }

    $id = $nrTelefone;
    $texto = $cliente;
    if (strlen($idDocumento) > 11) {
        // Fluxo normal para CNPJ
        $agi->verbose("Seguindo fluxo de CNPJ para: $idDocumento");
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
                EtapaConfirmacao_CNPJ::handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $nrProtocolo, $idDocumento, $nrCarteirinha); // Encaminhando para classe de confirmação.
                break;
            } elseif ($dtmf === '2') {
                $agi->verbose("Usuário digitou '2' para Não.");
                include_once 'etapaNegacao_CNPJ.php'; // Incluir arquivo da etapa de negação
                EtapaNegacao_CNPJ::handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $cliente, $alawFile, $nrProtocolo); // Encaminhando para classe de negação.
                break;
            } elseif (empty($dtmf)) {
                $agi->verbose("Usuário não respondeu.");
                //Estagio 1 (Cliente atendeu mas não respondeu nenhuma opção)
                $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '1');
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

        $max_attempts = 3;
        $attempt = 0;
        while ($attempt < $max_attempts) {
            $dtmf = $agi->get_data('beep', 10000, 1)['result']; // Captura a entrada do usuário
            if ($dtmf === '1') {
                include_once 'etapaConfirmacao.php'; // Incluir arquivo da etapa de confirmação
                delAudio($alawFile, $agi);
                EtapaConfirmacao::handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $nrProtocolo, $dtNasc, $idDocumento, $nrCarteirinha); // Encaminhando para classe de confirmação.
                break;
            } elseif ($dtmf === '2') {
                $agi->verbose("Usuário digitou '2' para Não.");
                include_once 'etapaNegacao.php'; // Incluir arquivo da etapa de negação
                EtapaNegacao::handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $cliente, $alawFile, $nrProtocolo); // Encaminhando para classe de negação.
                break;
            } elseif (empty($dtmf)) {
                $agi->verbose("Usuário não respondeu.");
                //Estagio 1 (Cliente atendeu mas não respondeu nenhuma opção)
                $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '1');
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
            //Estagio 1 (Cliente atendeu mas não respondeu nenhuma opção)
            $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '1');
        }
    }

} catch (Exception $e) {
    $agi->verbose("Erro ao sintetizar ou converter áudio: " . $e->getMessage());
}

// Finalizar a chamada AGI
$agi->hangup();
?>
