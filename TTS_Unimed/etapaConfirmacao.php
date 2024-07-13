<?php
// Arquivo: etapaConfirmacao.php
/* *********************************************
 * @Programador: Lucas Daniel Chaves
 * @Data: 2024-07-12
 * @Descrição: Classe responsável pela Etapa de confirmaçao, fluxo.
 ********************************************* */



class EtapaConfirmacao {
    public static function handle($agi, $ibmWatson, $converter, $work_dir, $voice, $id) {
        self::etapaInicial($agi, $ibmWatson, $converter, $work_dir, $voice, $id);
        $result = $agi->get_data('beep', 10000, 1);
        $dtmf = $result['result'];


        //usuario digitou sim
        if ($dtmf === '1') {
            self::etapaFinal($agi, $ibmWatson, $converter, $work_dir, $voice, $id);
        } elseif ($dtmf === '2' || empty($dtmf)) {
            $agi->verbose("Usuário digitou '2' para Não ou não respondeu.");
            // Lógica para lidar com resposta '2' ou sem resposta
        } else {
            $agi->verbose("Resposta inválida: $dtmf");
            // Lógica para lidar com resposta inválida
        }
    }

    private static function etapaInicial($agi, $ibmWatson, $converter, $work_dir, $voice, $id) {
        $agi->verbose("Usuário digitou '1' para Sim.");
        $texto = "Sou a Anne da Unimed
                    Blumenau, informo que esta
                    ligação está sendo gravada
                    sob o número de protocolo
                    $id,
                    caso queira ouvir
                    novamente digite um";
        $alawFile = self::mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter);
        self::delAudio($alawFile, $agi);
    }

    private static function etapaFinal($agi, $ibmWatson, $converter, $work_dir, $voice, $id) {
        $agi->verbose("Usuário digitou 1 para escutar novamente.");
        $texto = "Você digitou 1! Encerrando a chamada.";
        $outputFile = $ibmWatson->synthesizeAudio($texto, $voice, $id);
        $alawFile = $converter->convertToAlaw($outputFile, $work_dir, $id);
        $agi->exec("Playback", $alawFile);
        $agi->hangup();
    }

    private static function mkAudio($texto, $voice, $id, $work_dir, $agi, $ibmWatson, $converter) {
        $outputFile = $ibmWatson->synthesizeAudio($texto, $voice, $id);
        $alawFile = $converter->convertToAlaw($outputFile, $work_dir, $id);
        $agi->exec("Backgroud", $alawFile);
        $agi->verbose("Gerado arquivo de áudio temporário " . $alawFile . " e seu arquivo auxiliar " . $alawFile_aux);
        return $alawFile;
    }

    private static function delAudio($alawFile, $agi) {
        unlink($alawFile);
        $alawFile_aux = $alawFile . ".alaw";
        unlink($alawFile_aux);
        $agi->verbose("Removido arquivo temporário: " . $alawFile . " e seu arquivo auxiliar " . $alawFile_aux);
    }
}
?>