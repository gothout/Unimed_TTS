<?php

// Inclua a classe IBMWatsonTextToSpeech
require_once 'IBMWatsonTextToSpeech.php';

// Defina suas credenciais e configurações
$apiKey = '3BSntQGR_4zEjkh6WqOZqIlpe9T6xTi-iuwzJXXb5IDq';
$baseUrl = 'https://api.us-south.text-to-speech.watson.cloud.ibm.com/instances/0f310545-b3e4-44af-9407-c954cd25b03f';
$outputDir = '/var/lib/asterisk/agi-bin/TTS_Unimed/temp_audios'; // Substitua pelo diretório onde deseja salvar os arquivos de áudio

// Texto, voz e ID para o áudio
$texto = 'Olá, isso é um teste de síntese de fala usando IBM Watson.';
$voice = 'pt-BR_IsabelaV3Voice';
$id = 'teste123';

// Crie uma instância da classe IBMWatsonTextToSpeech
$ibmWatson = new IBMWatsonTextToSpeech($apiKey, $baseUrl, $outputDir);

// Chame o método para sintetizar o áudio
$ibmWatson->synthesizeAudio($texto, $voice, $id);
