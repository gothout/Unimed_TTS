<?php
class PutUnimed
{
    //private $url = 'https://ords-homol.unimedblumenau.com.br/ords/app/api/Telefonica/retorna_estagio_ligacao';
    private $url = 'http://192.168.100.116:5000/ords/app/api/Telefonica/retorna_estagio_ligacao';
    private $authHeader = 'Basic c2lnbWFjb21fdXJhOktxbzVkZUlsMFk=';

    public function sendRequest($nrProtocolo, $nrEtapa, $nrEstagio)
    {
        // Dados a serem enviados na requisição
        $data = array(
            'nrProtocolo' => $nrProtocolo,
            'nrEtapa' => $nrEtapa,
            'nrEstagio' => $nrEstagio
        );

        // Inicializar cURL
        $ch = curl_init($this->url);

        // Configurar opções do cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: ' . $this->authHeader
        ));

        // Executar a requisição
        $response = curl_exec($ch);

        // Verificar se houve algum erro
        if (curl_errno($ch)) {
            throw new Exception('Erro na requisição: ' . curl_error($ch));
        }

        // Verificar o código de resposta HTTP
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception('Erro na requisição, código HTTP: ' . $httpCode);
        }

        // Fechar a sessão cURL
        curl_close($ch);

        // Retornar a resposta
        return $response;
    }
}
?>
