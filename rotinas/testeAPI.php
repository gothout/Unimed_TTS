<?php
// URL da API da Unimed para obter a lista de contatos
$api_unimed = "https://ords-homol.unimedblumenau.com.br/ords/app/api/Telefonica/obter_lista";

// Token de autorização
$token = 'Basic c2lnbWFjb21fdXJhOktxbzVkZUlsMFk=';  // Substitua pelo seu token real

// Inicialização da sessão cURL
$ch = curl_init();

// Configuração da URL e outros parâmetros
curl_setopt($ch, CURLOPT_URL, $api_unimed);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retorna o resultado da transferência como string
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: ' . $token,  // Cabeçalho de autorização
    'Content-Type: application/json'  // Tipo de conteúdo
));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Apenas para desenvolvimento; remover em produção

// Executa a requisição e obtém a resposta
$response = curl_exec($ch);

// Verifica se houve erro na requisição
if(curl_errno($ch)) {
    $error_message = curl_error($ch);
    // Log do erro (pode ser substituído por outra forma de log)
    error_log("Erro na requisição cURL: {$error_message}");
    // Opcional: retornar ou lançar uma exceção
} else {
    // Obtém informações sobre a requisição
    $info = curl_getinfo($ch);
    echo "Código HTTP: " . $info['http_code'] . "\n";
    echo "Tempo total: " . $info['total_time'] . " segundos\n";
    echo "Resposta: " . $response . "\n";
}

// Fecha a sessão cURL
curl_close($ch);
?>
