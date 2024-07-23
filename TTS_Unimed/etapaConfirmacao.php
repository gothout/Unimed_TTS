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
$mensagem_finalv1 = 'mensagem_finalv1.wav';
/*Estou ligando, porque identificamos valores em aberto com o vencimento em..*/

$mensagem_finalv2 = 'mensagem_finalv2.wav';
/*no valor de.. */

$mensagem_finalv3 = 'mensagem_finalv3.wav';
/*título número..*/

$mensagem_finalv4 = 'mensagem_finalv4.wav'; 
/*esta fatura está em atraso a..*/

$mensagem_finalv5 = 'mensagem_finalv5.wav';
/*Falar a mesma coisa sobre as demais faturas em aberto caso tenha. Informamos que o prazo para pagamento a fim de evitar o cancelamento é até..*/

$mensagem_finalv6 = 'mensagem_finalv6.wav';
/*Em caso de dúvidas, entre em contato com a nossa equipe pelo telefone zero, oito, zero, zero, seis, quatro, sete, zero, zero, dois, seis.*/

$agradecimento = 'agradecimento.wav';
/*A Unimed Blumenau agradece por sua atenção!*/


class EtapaConfirmacao {
    public static function handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $nrProtocolo, $dtNasc, $idDocumento, $nrCarteirinha) {
        global $nrEtapa, $putUnimedAPI;
        $agi->verbose("Usuário digitou '1' para sim.");
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
        global $imut_audiosDir, $mensagem_finalv1, $mensagem_finalv2, $mensagem_finalv3, $mensagem_finalv4, $mensagem_finalv5, $mensagem_finalv6, $agradecimento, $nrProtocolo, $nrEtapa, $putUnimedAPI;
        //Estagio 6 (Cliente acertou sua data de aniversário, redirecionado ao aúdio final de suas faturas)
        $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '6');
        $data_vencida = "18 de Julho de 2002";
        $valor_atraso = "1200 reais e 20 centavos";
        $numero_titulo = '11671213920';
        $dias_atraso = "17 dias.";
        $diaPrazo = "20 de Julho de 2002";
    
    
        // Gerando áudio para data vencida
        $texto = $data_vencida;
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
        $data_vencida = "/" . $id . '_data_vencida.wav'; // Certifique-se de que $work_dir já termina com '/'
        exec("mv {$alawFile} {$work_dir}{$data_vencida}");
        exec("mv {$alawFile}.alaw {$work_dir}{$data_vencida}.alaw");
    
        // Gerando áudio para valor em atraso
        $texto = $valor_atraso;
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
        $valor_atraso = "/" . $id . '_valor_atraso.wav'; // Certifique-se de que $work_dir já termina com '/'
        exec("mv {$alawFile} {$work_dir}{$valor_atraso}");
        exec("mv {$alawFile}.alaw {$work_dir}{$valor_atraso}.alaw");
    
        // Gerando áudio para numero de titulo
        $texto = self::numberToWords($numero_titulo);
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
        $numero_titulo = "/" . $id . '_numero_titulo.wav';
        exec("mv {$alawFile} {$work_dir}{$numero_titulo}");
        exec("mv {$alawFile}.alaw {$work_dir}{$numero_titulo}.alaw");
    
    
        // Gerando áudio para dias de atraso
        $texto = $dias_atraso;
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
        $dias_atraso = "/" . $id . '_dias_atraso.wav';
        exec("mv {$alawFile} {$work_dir}{$dias_atraso}");
        exec("mv {$alawFile}.alaw {$work_dir}{$dias_atraso}.alaw");
    
    
        // Gerando áudio para dias de prazo
        $texto = $diaPrazo;
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
        $diaPrazo = "/" . $id . '_diaPrazo.wav';
        exec("mv {$alawFile} {$work_dir}{$diaPrazo}");
        exec("mv {$alawFile}.alaw {$work_dir}{$diaPrazo}.alaw");
    
        // Reproduzindo áudios
        /* Estou ligando, porque identificamos valores em aberto com o vencimento em.. data vencimento */
        $agi->verbose("Texto imutável reproduzido: " . $imut_audiosDir . $mensagem_finalv1);
        $agi->exec("Playback", $imut_audiosDir . $mensagem_finalv1);
        $agi->verbose("Texto temporario reproduzido: " . $work_dir . $data_vencida);
        $agi->exec("Playback", $work_dir . $data_vencida);
        // Excluindo arquivo temporário
        $alawFile = $work_dir . $data_vencida;
        self::delAudio($alawFile, $agi);
    
        /* no valor de.. */
        $agi->verbose("Texto imutável reproduzido: " . $imut_audiosDir . $mensagem_finalv2);
        $agi->exec("Playback", $imut_audiosDir . $mensagem_finalv2);
        $agi->verbose("Texto temporario reproduzido: " . $work_dir . $valor_atraso);
        $agi->exec("Playback", $work_dir . $valor_atraso);
        // Excluindo arquivo temporário
        $alawFile = $work_dir . $valor_atraso;
        self::delAudio($alawFile, $agi);
    
        /* título número.. */
        $agi->verbose("Texto imutável reproduzido: " . $imut_audiosDir . $mensagem_finalv3);
        $agi->exec("Playback", $imut_audiosDir . $mensagem_finalv3);
        $agi->verbose("Texto temporario reproduzido: " . $work_dir . $numero_titulo);
        $agi->exec("Playback", $work_dir . $numero_titulo);
        // Excluindo arquivo temporário
        $alawFile = $work_dir . $numero_titulo;
        self::delAudio($alawFile, $agi);
    
        /* esta fatura está em atraso a.. */
        $agi->verbose("Texto imutável reproduzido: " . $imut_audiosDir . $mensagem_finalv4);
        $agi->exec("Playback", $imut_audiosDir . $mensagem_finalv4);
        $agi->verbose("Texto temporario reproduzido: " . $work_dir . $dias_atraso);
        $agi->exec("Playback", $work_dir . $dias_atraso);
        // Excluindo arquivo temporário
        $alawFile = $work_dir . $dias_atraso;
        self::delAudio($alawFile, $agi);
    
        /* Falar a mesma coisa sobre as demais faturas em aberto caso tenha. Informamos que o prazo para pagamento a fim de evitar o cancelamento é até.. */
        $agi->verbose("Texto imutável reproduzido: " . $imut_audiosDir . $mensagem_finalv5);
        $agi->exec("Playback", $imut_audiosDir . $mensagem_finalv5);
        $agi->verbose("Texto temporario reproduzido: " . $work_dir . $diaPrazo);
        $agi->exec("Playback", $work_dir . $diaPrazo);
        // Excluindo arquivo temporário
        $alawFile = $work_dir . $diaPrazo;
        self::delAudio($alawFile, $agi);
    
        $agi->verbose("Texto imutável reproduzido: " . $imut_audiosDir . $mensagem_finalv6);
        $agi->exec("Playback", $imut_audiosDir . $mensagem_finalv6);



        $agi->verbose("Texto imutável reproduzido: " . $imut_audiosDir . $agradecimento);
        //Estagio 7 (Cliente chegou até o final e escutou tudo)
        $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '7');
        $agi->exec("Playback", $imut_audiosDir . $agradecimento);
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
