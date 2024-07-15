<?php
// Arquivo: etapaConfirmacao.php
/* *********************************************
 * @Programador: Lucas Daniel Chaves
 * @Data: 2024-07-12
 * @Descrição: Classe responsável pela Etapa de confirmaçao, fluxo.
 ********************************************* */

$imut_audiosDir = '/var/lib/asterisk/agi-bin/TTS_Unimed/imut_audios/';

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



$ouvir_novamente_1 = 'ouvir_novamente_1.wav';
/*Caso queira ouvir novamente, digite 1*/

class EtapaConfirmacao {
    public static function handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id) {
        self::etapaConfirmacao_P2_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id);
    }

    private static function etapaConfirmacao_P2_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id) {
        // Repassar variáveis globais do script para função privada
        global $imut_audiosDir, $etapa_inicial_cpf1v1, $ouvir_novamente_1;
        $number = $id;
        $texto = self::numberToWords($number);
        // Gerando número de protocolo
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
    
        $agi->verbose("Usuário digitou '1' para Sim.");
        
        // Loop para permitir ouvir novamente
        while (true) {
            /*  @@@@@@
                Depois mudar aqui para apenas reproduzir o protocolo 
                @@@@@@*/
            $texto = $imut_audiosDir . $etapa_inicial_cpf1v1;
            $agi->verbose("Texto imutavel reproduzido: " . $texto);
            $agi->exec("Playback", $texto);
            $agi->exec("Playback", $alawFile);
            $texto = $imut_audiosDir . $ouvir_novamente_1;
            $agi->verbose("Texto imutavel reproduzido: " . $texto);
            $agi->exec("Playback", $texto);
            // Pede a entrada do usuário
            $result = $agi->get_data('beep', 10000, 1);
            $dtmf = $result['result'];
            if ($dtmf === '1') {
                $agi->verbose("Usuário digitou '1' para ouvir novamente.");
            } else {
                $agi->verbose("Não respondeu, continuando fluxo.");
                self::delAudio($alawFile, $agi);
                self::etapaConfirmacao_P3_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $digite_novamente, $excedeu_tentativas);
                break;
            }
        }
    }

    private static function etapaConfirmacao_P3_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id) {
        // Repassar variáveis globais do script para função privada
        global $imut_audiosDir, $etapa_inicial_cpf2v1, $etapa_inicial_cpf2v2, $digite_novamente, $excedeu_tentativas;

        $id_assistenciaSaude = '0011671213931';
        $cpf = '11671213920';//cpf so para ter uma ideia
        $cpf_digits = 3;  // Número de dígitos do CPF que o usuário deve fornecer
        $max_attempts = 3;  // Máximo de tentativas permitidas
        // Aqui, assumindo que $id já é um número, podemos usá-lo diretamente
        $texto = self::numberToWords($id_assistenciaSaude);
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);

        $texto = $imut_audiosDir . $etapa_inicial_cpf2v1;
        $agi->verbose("Texto imutável reproduzido: " . $texto);
        $agi->exec("Playback", $texto);
        $agi->exec("Playback", $alawFile);

        $texto = $imut_audiosDir . $etapa_inicial_cpf2v2;
        $agi->verbose("Texto imutável reproduzido: " . $texto);
        $agi->exec("Playback", $texto);
        $agi->verbose("Usuário escutou o protocolo e foi solicitado o início dos 3 dígitos do CPF");
        self::delAudio($alawFile, $agi);

        $attempt = 0;
        while ($attempt < $max_attempts) {
            $attempt++;
            $agi->verbose("Tentativa $attempt de $max_attempts para fornecer os primeiros $cpf_digits dígitos do CPF.");
            
            // Captura a entrada do usuário
            $result = $agi->get_data('beep', 10000, $cpf_digits);
            $input_cpf = $result['result'];
            
            // Verifica se os primeiros $cpf_digits dígitos do $input_cpf correspondem aos do $cpf
            if (substr($input_cpf, 0, $cpf_digits) === substr($cpf, 0, $cpf_digits)) {
                $agi->verbose("Usuário forneceu os primeiros $cpf_digits dígitos corretamente: $input_cpf.");
                break;
            } else {
                $agi->verbose("Usuário não forneceu os primeiros $cpf_digits dígitos corretamente.");
                if ($attempt < $max_attempts) {
                    $agi->exec("Playback", $imut_audiosDir . $digite_novamente);  // Solicita que o usuário tente novamente
                } else {
                    $agi->exec("Playback", $imut_audiosDir . $excedeu_tentativas);
                    $agi->hangup();
                    break;
                }
            }
        }

    }   

    private static function etapaConfirmacao_P4_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id) {
        // Repassar variáveis globais do script para função privada
        global $imut_audiosDir, $etapa_inicial_cpf2v1, $etapa_inicial_cpf2v2, $digite_novamente;

        $id_protocolo = '0011671213931';
        $cpf = '11671213920';//cpf so para ter uma ideia
        $cpf_digits = 3;  // Número de dígitos do CPF que o usuário deve fornecer
        $max_attempts = 3;  // Máximo de tentativas permitidas
        // Aqui, assumindo que $id já é um número, podemos usá-lo diretamente
        $texto = self::numberToWords($id_protocolo);
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);

        $texto = $imut_audiosDir . $etapa_inicial_cpf2v1;
        $agi->verbose("Texto imutável reproduzido: " . $texto);
        $agi->exec("Playback", $texto);
        $agi->exec("Playback", $alawFile);

        $texto = $imut_audiosDir . $etapa_inicial_cpf2v2;
        $agi->verbose("Texto imutável reproduzido: " . $texto);
        $agi->exec("Playback", $texto);
        $agi->verbose("Usuário escutou o protocolo e foi solicitado o início dos 3 dígitos do CPF");

        $attempt = 0;
        while ($attempt < $max_attempts) {
            $attempt++;
            $agi->verbose("Tentativa $attempt de $max_attempts para fornecer os primeiros $cpf_digits dígitos do CPF.");
            // Captura a entrada do usuário
            $result = $agi->get_data('beep', 10000, $cpf_digits);
            $input_cpf = $result['result'];
            
            // Verifica se os primeiros $cpf_digits dígitos do $input_cpf correspondem aos do $cpf
            if (substr($input_cpf, 0, $cpf_digits) === substr($cpf, 0, $cpf_digits)) {
                $agi->verbose("Usuário forneceu os primeiros $cpf_digits dígitos corretamente: $input_cpf.");
                break;
            } else {
                $agi->verbose("Usuário não forneceu os primeiros $cpf_digits dígitos corretamente.");
                if ($attempt < $max_attempts) {
                    $agi->exec("Playback", $imut_audiosDir . $digite_novamente);  // Solicita que o usuário tente novamente
                }
            }
        }
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
