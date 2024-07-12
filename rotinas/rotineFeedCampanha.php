<?php

/**
 * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
 * @@@@@@@@ Lucas D. @@@@@@@@@@@@
 * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
 * 
 * Desenvolvido por Lucas D.
 * Contato: lucas.chaves@wonit.com.br.com
 * 
 * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
 * @@@@@@@@ Lucas D. @@@@@@@@@@@@
 * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
 */

// Função para parar uma campanha
function parar_campanha($api_xc, $token_Xcontact, $campanha_id) {
    // Configuração dos headers da requisição
    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token_Xcontact,
        'Content-Type: application/json'
    );

    // Corpo da requisição (body)
    $body = array(
        'status' => 'F' // Define o status como "F" para parar a campanha
    );

    // URL da API para parar a campanha
    $url = "{$api_xc}/{$campanha_id}";

    // Inicialização da sessão cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); // Envio do JSON no corpo da requisição
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execução da requisição e obtenção do código de resposta HTTP
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verifica se a requisição foi bem sucedida (código 200)
    if ($httpcode == 200) {
        return json_decode($response, true); // Retorna os dados da resposta em formato JSON
    } else {
        // Caso haja falha na requisição, retorna um array com detalhes do erro
        return array(
            'error' => "Request failed with status code {$httpcode}",
            'details' => $response
        );
    }
}

// Função para zerar uma campanha
function zerar_campanha($api_xc_v2, $token_Xcontact, $campanha_id) {
    // Configuração dos headers da requisição
    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token_Xcontact
    );

    // URL da API para zerar a campanha
    $url = "{$api_xc_v2}/{$campanha_id}/zerar";

    // Inicialização da sessão cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execução da requisição e obtenção do código de resposta HTTP
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verifica se a requisição foi bem sucedida (código 200)
    if ($httpcode == 200) {
        return json_decode($response, true); // Retorna os dados da resposta em formato JSON
    } else {
        // Caso haja falha na requisição, retorna um array com detalhes do erro
        return array(
            'error' => "Request failed with status code {$httpcode}",
            'details' => $response
        );
    }
}

// Função para remover todos os contatos de uma campanha
function remover_todos_contatos($api_xc, $token_Xcontact, $campanha_id) {
    // Configuração dos headers da requisição
    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token_Xcontact
    );

    // URL da API para remover todos os contatos da campanha
    $url = "{$api_xc}/{$campanha_id}/contato";

    // Inicialização da sessão cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execução da requisição e obtenção do código de resposta HTTP
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verifica se a requisição foi bem sucedida (código 200)
    if ($httpcode == 200) {
        return json_decode($response, true); // Retorna os dados da resposta em formato JSON
    } else {
        // Caso haja falha na requisição, retorna um array com detalhes do erro
        return array(
            'error' => "Request failed with status code {$httpcode}",
            'details' => $response
        );
    }
}

// Função para alimentar uma campanha com novos contatos
function alimentar_campanha($api_xc_v2, $token_Xcontact, $campanha_id, $contatos) {
    // Configuração dos headers da requisição
    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token_Xcontact,
        'Content-Type: application/json'
    );

    // URL da API para importar contatos para a campanha
    $url = "{$api_xc_v2}/{$campanha_id}/contatos_import";

    // Inicialização da sessão cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($contatos)); // Envio do JSON no corpo da requisição
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execução da requisição e obtenção do código de resposta HTTP
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verifica se a requisição foi bem sucedida (código 200 ou 201)
    if ($httpcode == 200 || $httpcode == 201) {
        return json_decode($response, true); // Retorna os dados da resposta em formato JSON
    } else {
        // Caso haja falha na requisição, retorna um array com detalhes do erro
        return array(
            'error' => "Request failed with status code {$httpcode}",
            'details' => $response
        );
    }
}

// Função para ativar uma campanha
function ativar_campanha($api_xc, $token_Xcontact, $campanha_id) {
    // Configuração dos headers da requisição
    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token_Xcontact,
        'Content-Type: application/json'
    );

    // Corpo da requisição (body)
    $body = array(
        'status' => 'A' // Define o status como "A" para ativar a campanha
    );

    // URL da API para ativar a campanha
    $url = "{$api_xc}/{$campanha_id}";

    // Inicialização da sessão cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); // Envio do JSON no corpo da requisição
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execução da requisição e obtenção do código de resposta HTTP
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verifica se a requisição foi bem sucedida (código 200)
    if ($httpcode == 200) {
        return json_decode($response, true); // Retorna os dados da resposta em formato JSON
    } else {
        // Caso haja falha na requisição, retorna um array com detalhes do erro
        return array(
            'error' => "Request failed with status code {$httpcode}",
            'details' => $response
        );
    }
}

// Função para adicionar uma linha de log ao arquivo de log da rotina da campanha
function log_campanha($log_message) {
    $log_file = '/var/log/campanha_rotina.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $log_message = "{$timestamp} {$log_message}\n";

    // Abre o arquivo de log em modo de escrita, criando-o se não existir
    $fp = fopen($log_file, 'a');

    // Escreve a mensagem de log no arquivo
    fwrite($fp, $log_message);

    // Fecha o arquivo de log
    fclose($fp);
}

$api_xc = 'http://localhost:8001/api/v3/campanha';
$api_xc_v2 = 'http://localhost:8001/api/v2/campanha'; // API funcional para criar campanha baseado em argumento json
$token_Xcontact = '1e5a475b7421c20cf0edb60543b20d406a2e55fd2619f5f04ee10dbdc41a007b';
$campanha_id = '8'; //ID da campanha que irá ser substituida

//Carregamento de contatos
$contatos = array(
    array('TELEFONE' => '991447700', 'NOME' => 'Lucas')
);

//Rotina de troca com APIS
log_campanha("Rotina iniciada iniciada");

// 1 - Parar campanha
$resultado = parar_campanha($api_xc, $token_Xcontact, $campanha_id);
log_campanha("Campanha {$campanha_id} parada com sucesso.");

// 2 - Zerar campanha
$resultado = zerar_campanha($api_xc_v2, $token_Xcontact, $campanha_id);
log_campanha("Campanha {$campanha_id} registros zerados com sucesso.");

// 3 - Remover usuarios
$resultado = remover_todos_contatos($api_xc, $token_Xcontact, $campanha_id);
log_campanha("Campanha {$campanha_id} contatos removidos com sucesso.");

// 4 - Alimentar campanha
$resultado = alimentar_campanha($api_xc_v2, $token_Xcontact, $campanha_id, $contatos);
log_campanha("Campanha {$campanha_id} alimentada com sucesso.");

// Exemplo de uso das funções (substitua com os dados reais da sua aplicação)
$resultado = ativar_campanha($api_xc, $token_Xcontact, $campanha_id);
log_campanha("Campanha {$campanha_id} ativada com sucesso.");

log_campanha("Rotina finalizada com sucesso.");

?>
