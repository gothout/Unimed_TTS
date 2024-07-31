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
        
        // Configuração de tentativa
        $maxRetries = 5;
        $retryCount = 0;
        
        do {
            // Configuração da requisição cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "apikey:{$this->apiKey}");
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 5 segundos
            
            // Executa a requisição
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_errno($ch);
            
            // Escreve no log
            // $this->writeLog("Requisição para sintetização de áudio - Texto: $text, Voz: $voice, ID: $id, Tentativa: " . ($retryCount + 1));
            
            // Verifica se a requisição foi bem-sucedida
            if ($httpCode == 200 && $curlError == 0) {
                // Define o caminho completo do arquivo de saída
                $outputFile = "{$this->outputDir}/{$id}.wav";
                
                // Salva o arquivo de áudio no diretório especificado
                file_put_contents($outputFile, $response);
                
                // Escreve no log
                // $this->writeLog("Áudio sintetizado com sucesso - Arquivo: $outputFile");
                
                curl_close($ch);
                return $outputFile; // Retorna o nome do arquivo de áudio salvo
            } else {
                // Escreve no log
                // $this->writeLog("Erro na requisição - HTTP Code: $httpCode, cURL Error: $curlError, Resposta: $response");
                
                curl_close($ch);
                $retryCount++;
                sleep(1); // Aguarda 1 segundo antes de tentar novamente
            }
        } while ($retryCount < $maxRetries);
        
        throw new Exception("Falha ao gerar o áudio após {$maxRetries} tentativas.");
    }
}
?>
