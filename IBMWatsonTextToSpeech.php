<?php

class IBMWatsonTextToSpeech {
    
    private $apiKey;
    private $baseUrl;
    private $outputDir; // Novo atributo para o diretório de saída
    
    public function __construct($apiKey, $baseUrl, $outputDir) {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->outputDir = $outputDir; // Configura o diretório de saída
    }
    
    // Função privada para escrever no log
    // private function writeLog($message) {
    //     $logFile = "{$this->outputDir}/ibm_watson_log.txt";
    //     $timestamp = date('Y-m-d H:i:s');
    //     $logMessage = "[$timestamp] $message\n";
    //     file_put_contents($logFile, $logMessage, FILE_APPEND);
    // }
    
    public function synthesizeAudio($text, $voice, $id) {
        // URL completa para a requisição
        $url = "{$this->baseUrl}/v1/synthesize?voice={$voice}";
        
        // Headers da requisição
        $headers = [
            'Content-Type: application/json',
            'Accept: audio/wav;rate=8000'
        ];
        
        // Dados JSON para enviar na requisição POST
        $data = json_encode([
            'text' => $text
        ]);
        
        // Configuração da requisição cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "apikey:{$this->apiKey}");
        
        // Executa a requisição
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Escreve no log
        // $this->writeLog("Requisição para sintetização de áudio - Texto: $text, Voz: $voice, ID: $id");
        
        // Verifica se a requisição foi bem-sucedida
        if ($httpCode == 200) {
            // Define o caminho completo do arquivo de saída
            $outputFile = "{$this->outputDir}/{$id}.wav";
            
            // Salva o arquivo de áudio no diretório especificado
            file_put_contents($outputFile, $response);
            
            // Escreve no log
            // $this->writeLog("Áudio sintetizado com sucesso - Arquivo: $outputFile");
            
            return $outputFile; // Retorna o nome do arquivo de áudio salvo
        } else {
            // Escreve no log
            // $this->writeLog("Erro na requisição - HTTP Code: $httpCode, Resposta: $response");
            
            throw new Exception("Erro na requisição: {$httpCode} - {$response}");
        }
        
        // Fecha a conexão cURL
        curl_close($ch);
    }
}

?>
