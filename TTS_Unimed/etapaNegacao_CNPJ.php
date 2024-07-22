<?php
// Arquivo: etapaConfirmacao.php
/* *********************************************
 * @Programador: Lucas Daniel Chaves
 * @Data: 2024-07-12
 * @Descrição: Classe responsável pela Etapa de negacao, fluxo.
  ********************************************* */


require_once '/var/lib/asterisk/agi-bin/TTS_Unimed/apiUnimed/putUnimed.php'; //envio de put para api da Unimed
$putUnimedAPI = new PutUnimed();
$imut_audiosDir = '/var/lib/asterisk/agi-bin/TTS_Unimed/imut_audios/';

$nrEtapa = "2";
// Nome de arquivos de áudios imutáveis (sem extensão)
$digite_novamente = 'digite_novamente.wav';
/*Desculpe, não consegui confirmar a informação,
poderia digitar novamente?
*/

$seu_protocolo = "seu_protocolo.wav";

$quest_voceconhecev1_cnpj = 'quest_voceconhecev1_cnpj.wav';
/*Você conhece $cliente?*/

$quest_voceconhecev1_cnpj = 'quest_voceconhecev1_cnpj.wav';
/*Você conhece $cliente?*/

$quest_voceconhecev2 = 'quest_voceconhecev2.wav';
/*Digite "1 para Sim" e "2 para Não */

$atualiza_cadastro = 'atualiza_cadastro.wav';
/*Ok estarei atualizando meu
cadastro, obrigada!
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

$mensagem_final_negacao = 'mensagem_final_negacao.wav';


class EtapaNegacao_CNPJ {
    public static function handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $cliente, $alawFile, $nrProtocolo) {
        global $nrEtapa, $putUnimedAPI;
        $agi->verbose("Usuário digitou '2' para não.");
        //Estagio 0 (Destino confirmou não ser o cliente)
        $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '0');
        self::etapaNegacao_P2_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $cliente, $alawFile, $nrProtocolo);
    }

    private static function etapaNegacao_P2_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $cliente, $alawFile, $nrProtocolo) {
        global $imut_audiosDir, $quest_voceconhecev1_cnpj, $quest_voceconhecev2, $digite_novamente, $atualiza_cadastro, $nrProtocolo, $nrProtocolo, $nrEtapa, $putUnimedAPI;
        $agi->exec("Playback", $imut_audiosDir . $quest_voceconhecev1_cnpj);
        $agi->exec("Playback", $alawFile);
        self::delAudio($alawFile, $agi);
        $agi->exec("Playback", $imut_audiosDir . $quest_voceconhecev2);
        $agi->verbose("Solicitado ao usuario se conhece " . $cliente);

        $max_attempts = 5;
        $attempt = 0;
        while ($attempt < $max_attempts) {
            $dtmf = $agi->get_data('beep', 10000, 1)['result'];
            if ($dtmf === '1') {
                self::etapaNegacao_P3_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $cliente);
                break;
            } elseif ($dtmf === '2') {
                $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '1');
                $agi->exec("Playback", $imut_audiosDir . $atualiza_cadastro);
                $agi->hangup();
                //Implementar aqui codigo para atualizar base
                break;
            } elseif (empty($dtmf)) {
                $agi->verbose("Usuário não respondeu.");
                $agi->hangup();
                break;
            } else {
                $agi->verbose("Resposta inválida: $dtmf");
                if ($attempt < $max_attempts - 1) {
                    $agi->exec("Playback", $imut_audiosDir . $digite_novamente);
                }
            }
            $attempt++;
        }
        if ($attempt == $max_attempts) {
            $agi->verbose("Máximo de tentativas atingido sem resposta válida.");
            $agi->hangup();
      }
    }

    private static function etapaNegacao_P3_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $nrProtocolo) {
        global $imut_audiosDir, $etapa_inicial_cpf1v1, $digite_novamente, $excedeu_tentativas,$ouvir_novamente_1, $seu_protocolo, $nrProtocolo, $nrEtapa, $putUnimedAPI;
        //Estagio 2 (Destino informou que conhece o cliente)
        $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '2');

        $texto = self::numberToWords($nrProtocolo);
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
        $agi->exec("Playback", $imut_audiosDir . $etapa_inicial_cpf1v1);
        $agi->verbose("Texto imutável reproduzido: ", $imut_audiosDir . $etapa_inicial_cpf1v1);
      
        while (true) {
          $agi->exec("Playback", $alawFile);
          $agi->verbose("Informado número de protocolo ao usuario: ", $nrProtocolo);
          //Estagio 3  (Destino escutou número de protocolo)
          $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '3');


          $agi->exec("Playback", $imut_audiosDir . $ouvir_novamente_1);
          $agi->verbose("Texto imutavel reproduzido: ", $imut_audiosDir . $ouvir_novamente_1);
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
              self::etapaNegacao_P4_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $digite_novamente, $excedeu_tentativas);
              break;
          }
      }
    }   

    private static function etapaNegacao_P4_audio($agi, $ibmWatson, $converter, $work_dir, $voice, $id, $excedeu_tentativas, $digite_novamente) {
      global $imut_audiosDir, $etapa_inicial_cpf1v1, $digite_novamente, $excedeu_tentativas,$ouvir_novamente_1, $mensagem_final_negacao, $agradecimento, $putUnimedAPI, $nrProtocolo, $nrEtapa;

      while (true) {
        //Estagio 4  (Estagio 4 (Destino escutou mensagem final))
        $response = $putUnimedAPI->sendRequest($nrProtocolo, $nrEtapa, '4');
        $agi->exec("Playback", $imut_audiosDir . $mensagem_final_negacao);
        $agi->verbose("Texto imutável reproduzido: ", $imut_audiosDir . $mensagem_final_negacao);  
        $result = $agi->get_data('beep', 10000, 1);
        $dtmf = $result['result'];
        if ($dtmf === '1') {
            $agi->verbose("Usuário digitou '1' para ouvir novamente.");
        } else {
            $agi->verbose("Não respondeu, continuando fluxo.");
            $agi->exec("Playback", $imut_audiosDir . $agradecimento);
            $agi->verbose("Texto imutável reproduzido: ", $imut_audiosDir . $agradecimento);
            $agi->hangup();
            break;
        }
    }
    
  }   
    private static function mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter) {
        $outputFile = $ibmWatson->synthesizeAudio($texto, $voice, $id);
        $alawFile = $converter->convertToAlaw($outputFile, $work_dir, $id);
        $agi->verbose("Gerado arquivo de áudio temporário: " . $alawFile);
        return $alawFile;
    }

    private static function delAudio($alawFile, $agi) {
        unlink($alawFile);
        $alawFile_aux = $alawFile . ".alaw";
        unlink($alawFile_aux);
        $agi->verbose("Removido arquivo temporário: " . $alawFile . " e seu arquivo auxiliar " . $alawFile_aux);
    }
    private static function numberToWords($number) {
        $words = [
            0 => 'zero', 1 => 'um', 2 => 'dois', 3 => 'três', 4 => 'quatro',
            5 => 'cinco', 6 => 'seis', 7 => 'sete', 8 => 'oito', 9 => 'nove'
        ];
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
