<?php
// Arquivo: etapaConfirmacao.php
/* *********************************************
 * @Programador: Lucas Daniel Chaves
 * @Data: 2024-07-12
 * @Descrição: Classe responsável pela Etapa de confirmaçao, fluxo.
 ********************************************* */

require_once '/var/lib/asterisk/agi-bin/TTS_Unimed/apiUnimed/putUnimed.php'; //envio de put para api da Unimed
$putUnimedAPI = new PutUnimed();

$nrEtapa = "1";
$imut_audiosDir = '/var/lib/asterisk/agi-bin/TTS_Unimed/imut_audios/';



$seu_protocolo = 'seu_protocolo.wav';
/*Seu protocolo é..*/

// Nome de arquivos de áudios imutáveis (sem extensão)
$digite_novamente = 'digite_novamente.wav';
/*Desculpe, não consegui confirmar a informação,
poderia digitar novamente?
*/

$excedeu_tentativas = 'excedeu_tentativas.wav';
/*Ainda não consegui confirmar a
informação, realizaremos mais
tarde uma nova tentativa de
contato. Caso prefira, entre em
contato com a nossa equipe
pelo telefone zero oito zero zero, seis quatro sete, zero zero dois seis. A
Unimed Blumenau agradece
por sua atenção
*/

$ouvir_novamente_1 = 'ouvir_novamente_1.wav';
/*Caso queira ouvir novamente, digite 1*/

$data_aniversario = 'data_aniversario.wav';
/*Agora, informando somente números, digite seu dia e mês de aniversário, com os 4 dígitos.*/

// Nome de arquivos de áudios imutáveis (sem extensão)
$etapa_inicial_cpf1v1 = 'etapa_inicial_cpf_1v1.wav';
/*Sou a Anne da Unimed
Blumenau, informo que esta
ligação está sendo gravada
sob o número de protocolo
XXXXXXXXXX
*/

$etapa_inicial_cpf2v1 = 'etapa_inicial_cpf2v1.wav';
/*Tenho uma informação
referente a questões
financeiras do seu plano de
assistência à saúde, código
*/
$etapa_inicial_cpf2v2 = 'etapa_inicial_cpf2v2.wav';
/*
por questão de segurança,
digite os três primeiros
números do seu CPF
*/

//Mensagens finais
$mensagemFinal2_v1 = 'mensagemFinal2_v1.wav';
/*Estou ligando, pois identificamos*/

$mensagemFinal2_v2 = 'mensagemFinal2_v2.wav';
/*valores em abertos:*/

$mensagemFinal2_v3 = 'mensagemFinal2_v3.wav';
/*O vencimento de seu */

$mensagemFinal2_v4 = 'mensagemFinal2_v4.wav'; 
/*valor em aberto é em */

$mensagemFinal2_v5 = 'mensagemFinal2_v5.wav';
/*no valor de */

$mensagemFinal2_v6 = 'mensagemFinal2_v6.wav';
/*com o titulo número*/

$mensagemFinal2_v7 = 'mensagemFinal2_v7.wav';
/*e seu prazo de pagamento é até */

$mensagemFinal2_v8 = 'mensagemFinal2_v8.wav';
/*Em caso de dúvidas, entre em contato com a nossa equipe pelo telefone zero oito zero zero seis quatro sete zero zero dois seis.*/

$mensagemFinal2_v10 = 'mensagemFinal2_v10.wav';
/*Titulo número...*/

$agradecimento = 'agradecimento.wav';
/*A Unimed Blumenau agradece por sua atenção!*/


class EtapaConfirmacao {
    public static function handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $nrProtocolo, $dtNasc, $idDocumento, $nrCarteirinha, $nrTitulo, $nrSaldoTitulo, $nrDiasAtraso, $qtdFaturas, $prazoPagto) {
        global $nrEtapa, $putUnimedAPI;
        $agi->verbose("Usuário digitou '1' para sim.");
        $agi->verbose("Confirmacao: Protocolo: $nrProtocolo, Nascimento: $dtNasc, Documento: $idDocumento, Carteirinha: $nrCarteirinha");
        $agi->verbose("Quantidade de faturas: " . $qtdFaturas);
        for ($i = 1; $i <= $qtdFaturas; $i++) {
            $agi->verbose("Quantidade de faturas: " . $qtdFaturas[$i - 1]);
            $agi->verbose("Quantidade de dias de atraso: " . $nrDiasAtraso[$i - 1]);
            $agi->verbose("NR_TITULO[$i]: " . $nrTitulo[$i - 1]);
            $agi->verbose("VL_SALDO_TITULO[$i]: " . $nrSaldoTitulo[$i - 1]);
            $agi->verbose("DT_DIAS_ATRASO[$i]: " . $nrDiasAtraso[$i - 1]);
        }
        $agi->verbose("Prazo para pagamento: " . $prazoPagto);
        //Estagio 0 (Cliente confirmou ser ele)
        $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '0');
        self::etapaConfirmacao_P2_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $nrProtocolo, $dtNasc, $idDocumento, $nrCarteirinha);
    }

    private static function etapaConfirmacao_P2_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $nrProtocolo, $dtNasc, $idDocumento) {
        // Repassar variáveis globais do script para função privada
        global $imut_audiosDir, $etapa_inicial_cpf1v1, $ouvir_novamente_1, $seu_protocolo, $nrEtapa, $putUnimedAPI;
        $texto = self::numberToWords($nrProtocolo);
        // Gerando número de protocolo
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
        $agi->exec("Playback", $imut_audiosDir . $etapa_inicial_cpf1v1);
        $agi->verbose("Texto imutavel reproduzido: ", $imut_audiosDir . $ouvir_novamente_1);
        // Loop para permitir ouvir novamente
        while (true) {
            $agi->exec("Playback", $alawFile);
            //Estagio 1 (Cliente escutou protocolo)
            $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '1');
            $agi->verbose("Informado número de protocolo ao usuario: ", $nrProtocolo);
            $agi->verbose("Texto imutavel reproduzido: " .  $imut_audiosDir . $etapa_inicial_cpf1v1);
            $agi->exec("Playback", $imut_audiosDir . $ouvir_novamente_1);
            // Pede a entrada do usuário
            $result = $agi->get_data('beep', 10000, 1);
            $dtmf = $result['result'];
            if ($dtmf === '1') {
                $agi->verbose("Usuário digitou '1' para ouvir novamente.");
                $agi->exec("Playback", $imut_audiosDir . $seu_protocolo);
                $agi->verbose("Texto imutável reproduzido: ", $imut_audiosDir . $seu_protocolo);
            } else {
                $agi->verbose("Não respondeu, continuando fluxo.");
                self::delAudio($alawFile, $agi);
                $agi->verbose("Parâmetros recebidos em etapaConfirmacao_P2_audio: nrProtocolo = $nrProtocolo, dtNasc = $dtNasc, idDocumento = $idDocumento");
                self::etapaConfirmacao_P3_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $digite_novamente, $excedeu_tentativas, $nrProtocolo, $dtNasc, $idDocumento);
                break;
            }
        }
    }

    private static function etapaConfirmacao_P3_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $nrProtocolo, $dtNasc, $idDocumento) {
        // Repassar variáveis globais do script para função privada
        global $imut_audiosDir, $etapa_inicial_cpf2v1, $etapa_inicial_cpf2v2, $digite_novamente, $excedeu_tentativas, $nrProtocolo, $dtNasc, $idDocumento, $nrEtapa, $putUnimedAPI, $nrCarteirinha;
        $idDocumento_digits = 3;  // Número de dígitos do CPF que o usuário deve fornecer
        $max_attempts = 3;  // Máximo de tentativas permitidas
        $agi->verbose("Parâmetros recebidos em etapaConfirmacao_P3_audio: nrCarteirinha = $nrCarteirinha, dtNasc = $dtNasc, idDocumento = $idDocumento");
        $texto = self::numberToWords($nrCarteirinha);
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);

        $agi->verbose("Texto imutável reproduzido: ", $imut_audiosDir . $etapa_inicial_cpf2v1);
        $agi->exec("Playback", $imut_audiosDir . $etapa_inicial_cpf2v1);
        $agi->exec("Playback", $alawFile);

        $agi->verbose("Texto imutável reproduzido: ", $imut_audiosDir . $etapa_inicial_cpf2v2);
        $agi->exec("Playback", $imut_audiosDir . $etapa_inicial_cpf2v2);
        $agi->verbose("Usuário escutou o protocolo e foi solicitado o início dos 3 dígitos do CPF");
        //Estagio 2 (Solicitado ao cliente os três primeiros digitos de seu CPF/CNPJ)
        $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '2');
        self::delAudio($alawFile, $agi);

        $attempt = 0;
        while ($attempt < $max_attempts) {
            $attempt++;
            $agi->verbose("Tentativa $attempt de $max_attempts para fornecer os primeiros $idDocumento_digits dígitos do CPF.");
            
            // Captura a entrada do usuário
            $result = $agi->get_data('beep', 10000, $idDocumento_digits);
            $input_cpf = $result['result'];
            
            // Verifica se os primeiros $idDocumento_digits dígitos do $input_cpf correspondem aos do $idDocumento
            if (substr($input_cpf, 0, $idDocumento_digits) === substr($idDocumento, 0, $idDocumento_digits)) {
                $agi->verbose("Usuário forneceu os primeiros $idDocumento_digits dígitos corretamente: $input_cpf.");
                self::etapaConfirmacao_P4_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id);
                break;
            } else {
                $agi->verbose("Usuário não forneceu os primeiros $idDocumento_digits dígitos corretamente.");
                if ($attempt < $max_attempts) {
                    $agi->exec("Playback", $imut_audiosDir . $digite_novamente);  // Solicita que o usuário tente novamente
                } else {
                    //Estagio 3 (Cliente errou suas 3 tentativas de CPF/CNPJ))
                    $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '3');
                    $agi->exec("Playback", $imut_audiosDir . $excedeu_tentativas);
                    $agi->hangup();
                    break;
                }
            }
        }
    }   

    private static function etapaConfirmacao_P4_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id) {
        global $digite_novamente, $excedeu_tentativas, $data_aniversario, $imut_audiosDir, $nrProtocolo, $nrEtapa, $putUnimedAPI, $dtNasc;

        $agi->verbose("Texto imutável reproduzido: " . $imut_audiosDir . $data_aniversario);
        $agi->exec("Playback", $imut_audiosDir . $data_aniversario);
        //Estagio 4 (Cliente acertou seus três primeiros digitos de seu CPF e foi solicitado seus 4 primeiros digitos de sua data de aniversário)
        $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '4');

        $birth = $dtNasc; // Data de aniversário esperada
        $birth_digits = 4;   // Número de dígitos a serem verificados
        $max_attempts = 3;   // Número máximo de tentativas
        $attempt = 0;
    
        while ($attempt < $max_attempts) {
            $attempt++;
            $agi->verbose("Tentativa $attempt de $max_attempts para fornecer os primeiros $birth_digits dígitos da data de aniversário dia/mês 0000");
    
            // Captura a entrada do usuário
            $result = $agi->get_data('beep', 10000, $birth_digits);
            $input_birth = $result['result'];

            // Comparação dos primeiros $birth_digits dígitos
            if (substr($input_birth, 0, $birth_digits) === substr($birth, 0, $birth_digits)) {
                $agi->verbose("Usuário forneceu os primeiros $birth_digits dígitos corretamente: $input_birth.");
                self::etapaConfirmacao_P5_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id);
                break;
            } else {
                $agi->verbose("Usuário não forneceu os primeiros $birth_digits dígitos corretamente.");
                if ($attempt < $max_attempts) {
                    $agi->exec("Playback", $imut_audiosDir . $digite_novamente);  // Solicita que o usuário tente novament
                } else {
                    //Estagio 5 (Cliente errou suas três tentativas de acerto em sua data de aniversário)
                    $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '5');
                    $agi->exec("Playback", $imut_audiosDir . $excedeu_tentativas);
                    $agi->hangup();
                    break;
                }
            }
        }
    }
   
    private static function etapaConfirmacao_P5_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id) {
        global $imut_audiosDir, $mensagemFinal2_v1, $mensagemFinal2_v2, $mensagemFinal2_v3, $mensagemFinal2_v4, $mensagemFinal2_v5, $mensagemFinal2_v6, $mensagemFinal2_v7, $mensagemFinal2_v8, $agradecimento, $nrProtocolo, $nrEtapa, $putUnimedAPI, $nrTitulo, $nrSaldoTitulo, $nrDiasAtraso, $qtdFaturas, $prazoPagto;
    
        // Estágio 6 (Cliente acertou sua data de aniversário, redirecionado ao áudio final de suas faturas)
        $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '6');
    
        // Gerando áudios temporários para serem utilizados num array de mensagens.
        for ($i = 1; $i <= $qtdFaturas; $i++) {
            // Gerando áudio temporário saldo. Ex: R$ 200.56 [quantidade de pendências abertas]
            $texto = "R$ " . $nrSaldoTitulo[$i - 1];
            $agi->verbose("Gerando áudio para " . $nrSaldoTitulo[$i - 1]);
            $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
            $saldoTitulo[$i] = "/" . $id . '_saldo_' . $i . '.wav';
            exec("mv {$alawFile} {$work_dir}{$saldoTitulo[$i]}");
            exec("mv {$alawFile}.alaw {$work_dir}{$saldoTitulo[$i]}.alaw");
            $agi->verbose("Gerado áudio para " . $work_dir . $saldoTitulo[$i]);
    
            // Gerando áudio título do saldo. Ex: 12344566 [quantidade de pendências abertas]
            $texto = self::numberToWords($nrTitulo[$i - 1]);
            $agi->verbose("Gerando áudio para " . $nrTitulo[$i - 1]);
            $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
            $idTitulo[$i] = "/" . $id . '_titulo_' . $i . '.wav';
            exec("mv {$alawFile} {$work_dir}{$idTitulo[$i]}");
            exec("mv {$alawFile}.alaw {$work_dir}{$idTitulo[$i]}.alaw");
            $agi->verbose("Gerado áudio para " . $work_dir . $idTitulo[$i]);
    
            // Gerando áudio para dias de atraso. Ex: 84 [quantidade de pendências abertas]
            $texto = self::numberToWords($nrDiasAtraso[$i - 1]);
            $agi->verbose("Gerando áudio para " . $nrDiasAtraso[$i - 1]);
            $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
            $diasAtraso[$i] = "/" . $id . '_diasAtraso_' . $i . '.wav';
            exec("mv {$alawFile} {$work_dir}{$diasAtraso[$i]}");
            exec("mv {$alawFile}.alaw {$work_dir}{$diasAtraso[$i]}.alaw");
            $agi->verbose("Gerado áudio para " . $work_dir . $diasAtraso[$i]);
        }
    
        $texto = self::numberToWords($qtdFaturas);
        $agi->verbose("Gerando áudio para " . $qtdFaturas);
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
        $quantidadeFaturas = "/" . $id . '_qtdFatura' . '.wav';
        exec("mv {$alawFile} {$work_dir}{$quantidadeFaturas}");
        exec("mv {$alawFile}.alaw {$work_dir}{$quantidadeFaturas}.alaw");
        $agi->verbose("Gerado áudio para " . $work_dir . $quantidadeFaturas);

        $agi->exec("Playback", $imut_audiosDir . $mensagemFinal2_v1);
        $agi->exec("Playback", $work_dir . $quantidadeFaturas);
        $agi->exec("Playback", $imut_audiosDir . $mensagemFinal2_v2);
        $agi->verbose("Quantidade de faturas abertas: " . $qtdFaturas);
        if ($qtdFaturas == 1) {
            $agi->exec("Playback", $imut_audiosDir . $mensagemFinal2_v10);
            $agi->exec("Playback", $work_dir . $idTitulo[1]);
            $agi->exec("Playback", $imut_audiosDir . $mensagemFinal2_v5);
            $agi->exec("Playback", $work_dir . $saldoTitulo[1]);
            $agi->exec("Playback", $imut_audiosDir . $mensagemFinal2_v4);
            $agi->exec("Playback", $work_dir . $diasAtraso[1]);
        } else {
            for ($i = 1; $i <= $qtdFaturas; $i++) {
                $agi->exec("Playback", $imut_audiosDir . $mensagemFinal2_v10);
                $agi->exec("Playback", $work_dir . $idTitulo[$i]);
                $agi->exec("Playback", $imut_audiosDir . $mensagemFinal2_v5);
                $agi->exec("Playback", $work_dir . $saldoTitulo[$i]);
                $agi->exec("Playback", $imut_audiosDir . $mensagemFinal2_v6);
                $agi->exec("Playback", $work_dir . $diasAtraso[$i]);
            }
        }
        $agi->verbose("Texto imutável reproduzido: " . $imut_audiosDir . $agradecimento);
        //Estagio 7 (Cliente chegou até o final e escutou tudo)
        $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '7');
        $agi->exec("Playback", $imut_audiosDir . $agradecimento);
        $agi->exec("Playback", $imut_audiosDir . $mensagemFinal2_v8);
        $agi->hangup();
    }
    
    private static function mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter) {
        $outputFile = $ibmWatson->synthesizeAudio($texto, $voice, $id);
        $alawFile = $converter->convertToAlaw($outputFile, $work_dir, $id);
        $agi->verbose("Gerado arquivo de áudio temporário: " . $alawFile);
        //removido playback para gerar o arquivo antes da mensagem geral para que seja mais nitido. Playback será gerado depois da função.
        //$agi->exec("Playback", $alawFile);
        return $alawFile;
    }

    private static function delAudio($alawFile, $agi) {
        unlink($alawFile);
        $alawFile_aux = $alawFile . ".alaw";
        unlink($alawFile_aux);
        $agi->verbose("Removido arquivo temporário: " . $alawFile . " e seu arquivo auxiliar " . $alawFile_aux);
    }
    private static function numberToWords($number) {
        // Array com as palavras correspondentes aos dígitos
        $words = [
            0 => 'zero', 1 => 'um', 2 => 'dois', 3 => 'três', 4 => 'quatro',
            5 => 'cinco', 6 => 'seis', 7 => 'sete', 8 => 'oito', 9 => 'nove'
        ];
    
        // Converte o número em string para iterar pelos dígitos
        $digits = str_split((string)$number);
        $result = [];
    
        foreach ($digits as $digit) {
            if (isset($words[$digit])) {
                $result[] = $words[$digit];
            }
        }
        return implode(' ', $result);
    }
}
?>