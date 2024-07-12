<?php
// AudioConverter.php

class AudioConverter {
    public function convertToAlaw($inputFile, $outputDir, $id) {
        //Definir nome de arquivo ALAW
        $alawFile = $outputDir . '/' . $id . '.wav.alaw';
         // Converter arquivo para ALAW usando ffmpeg
         exec("sox {$inputFile} -t al -e a-law {$alawFile}");
         //Verificar se a conversão foi bem-sucedida
        if (file_exists($alawFile)) {
            // Remover o arquivo WAV original após a conversão
            //unlink($inputFile);
            return $inputFile;
        } else {
            throw new Exception("Falha ao converter para ALAW.");
        }
    }
}
?>
