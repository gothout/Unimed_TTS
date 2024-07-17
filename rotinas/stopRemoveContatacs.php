<?php

/**
 * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
 * @@@@@@@@ Lucas D. @@@@@@@@@@@@
 * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
 * 
 * Desenvolvido por Lucas D.
 * Contato: lucas.chaves@wonit.com.br
 * 
 * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
 * @@@@@@@@ Lucas D. @@@@@@@@@@@@
 * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
 */

// Função para parar uma campanha
function parar_campanha($api_xc, $token_Xcontact, $campanha_id) {
    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token_Xcontact,
        'Content-Type: application/json'
    );

    $body = array(
        'status' => 'F' // Define o status como "F" para parar a campanha
    );

    $url = "{$api_xc}/{$campanha_id}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    log_campanha("Resposta parar campanha: HTTP {$httpcode} - {$response}");

    if ($httpcode == 200) {
        return json_decode($response, true);
    } else {
        return array(
            'error' => "Request failed with status code {$httpcode}",
            'details' => $response
        );
    }
}

// Função para zerar uma campanha
function zerar_campanha($api_xc_v2, $token_Xcontact, $campanha_id) {
    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token_Xcontact
    );

    $url = "{$api_xc_v2}/{$campanha_id}/zerar";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    log_campanha("Resposta zerar campanha: HTTP {$httpcode} - {$response}");

    if ($httpcode == 200) {
        return json_decode($response, true);
    } else {
        return array(
            'error' => "Request failed with status code {$httpcode}",
            'details' => $response
        );
    }
}

// Função para remover todos os contatos de uma campanha
function remover_todos_contatos($api_xc, $token_Xcontact, $campanha_id) {
    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token_Xcontact
    );

    $url = "{$api_xc}/{$campanha_id}/contato";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    log_campanha("Resposta remover contatos: HTTP {$httpcode} - {$response}");

    if ($httpcode == 200) {
        return json_decode($response, true);
    } else {
        return array(
            'error' => "Request failed with status code {$httpcode}",
            'details' => $response
        );
    }
}

// Função para ativar uma campanha
function ativar_campanha($api_xc, $token_Xcontact, $campanha_id) {
    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token_Xcontact,
        'Content-Type: application/json'
    );

    $body = array(
        'status' => 'A' // Define o status como "A" para ativar a campanha
    );

    $url = "{$api_xc}/{$campanha_id}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    log_campanha("Resposta ativar campanha: HTTP {$httpcode} - {$response}");

    if ($httpcode == 200) {
        return json_decode($response, true);
    } else {
        return array(
            'error' => "Request failed with status code {$httpcode}",
            'details' => $response
        );
    }
}

// Função para adicionar uma linha de log ao arquivo de log da rotina da campanha
function log_campanha($mensagem) {
    $data_hora = date('Y-m-d H:i:s');
    file_put_contents('/var/log/campanha_rotina.log', "[{$data_hora}] {$mensagem}\n", FILE_APPEND);
}

// IDs da campanha e tokens de autenticação (substituir pelos valores reais)
$campanha_id = 8;
$api_xc = 'http://localhost:8001/api/v3/campanha';
$api_xc_v2 = 'http://localhost:8001/api/v2/campanha'; // API funcional para criar campanha baseado em argumento json
$token_Xcontact = '1e5a475b7421c20cf0edb60543b20d406a2e55fd2619f5f04ee10dbdc41a007b';

// Parar a campanha
$res_parar = parar_campanha($api_xc, $token_Xcontact, $campanha_id);
log_campanha("Campanha {$campanha_id} parada: " . print_r($res_parar, true));

// Zerar a campanha
$res_zerar = zerar_campanha($api_xc_v2, $token_Xcontact, $campanha_id);
log_campanha("Campanha {$campanha_id} registros zerados: " . print_r($res_zerar, true));

// Remover todos os contatos da campanha
$res_remover = remover_todos_contatos($api_xc, $token_Xcontact, $campanha_id);
log_campanha("Campanha {$campanha_id} contatos removidos: " . print_r($res_remover, true));

// Ativar a campanha
//$res_ativar = ativar_campanha($api_xc, $token_Xcontact, $campanha_id);
//log_campanha("Campanha {$campanha_id} ativada: " . print_r($res_ativar, true));

// Finalização da rotina
log_campanha("Rotina de controle da campanha finalizada");

?>
