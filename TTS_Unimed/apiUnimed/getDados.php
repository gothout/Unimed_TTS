<?php
// getDados.php

// Classe para integração com a API da Unimed Blumenau para obter dados do beneficiário
class GetDados {
    private $baseUrl = 'https://ords-homol.unimedblumenau.com.br/ords/app/api/Telefonica/obter_dados_beneficiario';
    private $authToken = 'Authorization: Basic c2lnbWFjb21fdXJhOktxbzVkZUlsMFk=';

    // Método para buscar dados do beneficiário pelo número de telefone
    public function getData($nrTelefone) {
        $url = $this->baseUrl . '?nrTelefone=' . urlencode($nrTelefone);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            $this->authToken
        ));
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Erro na requisição cURL: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpcode >= 400) {
            throw new Exception('Erro na API da Unimed: Código ' . $httpcode);
        }
        
        return json_decode($response, true);
    }
}
?>
