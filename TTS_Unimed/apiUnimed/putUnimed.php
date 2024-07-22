<?php
class PutUnimed
{
    //private $url = 'https://ords-homol.unimedblumenau.com.br/ords/app/api/Telefonica/retorna_estagio_ligacao';
    private $url = 'http://192.168.100.53:5000/ords/app/api/Telefonica/retorna_estagio_ligacao';
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

        // Executar a requisição de forma assíncrona
        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        // Fechar a sessão cURL
        curl_multi_remove_handle($mh, $ch);
        curl_multi_close($mh);
    }
}
?>
