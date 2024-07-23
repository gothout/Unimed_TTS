#!/usr/bin/php -q
<?php
/* *********************************************
 * @Programador: Lucas Daniel Chaves
 * @Data: 2024-07-12
 * @Descrição: Script para buscar e apresentar dados do cliente
 ********************************************* */

// Classes necessárias
require_once 'getNome.php'; // Classe para obter o nome do cliente.
require_once '/var/lib/asterisk/agi-bin/TTS_Unimed/apiUnimed/getDados.php'; // Classe para buscar dados de clientes

// Função para buscar e apresentar dados do cliente
function buscarDadosCliente($nrTelefone) {
    try {
        // Instanciar classes necessárias
        $nomeFetcher = new ClienteNomeFetcher();
        $getDados = new GetDados();

        $cliente = $nomeFetcher->getNomeCliente($nrTelefone);
        echo "Nome do cliente: " . $cliente . PHP_EOL;
        echo "Buscando dados..." . PHP_EOL;

        $data = $getDados->getData($nrTelefone, $cliente);

        if (!is_array($data)) {
            throw new Exception("Dados do cliente não encontrados ou formato inválido.");
        }

        $clienteData = $data['Data'][0];
        $idDocumento = isset($clienteData['NR_CPF']) ? $clienteData['NR_CPF'] : (isset($clienteData['NR_CNPJ']) ? $clienteData['NR_CNPJ'] : '');
        $dtNasc = isset($clienteData['DT_NASC']) ? date("dmY", strtotime($clienteData['DT_NASC'])) : '';
        $nrProtocolo = isset($clienteData['NR_PROTOCOLO']) ? $clienteData['NR_PROTOCOLO'] : '';
        $nrCarteirinha = isset($clienteData['NR_CARTEIRINHA']) ? str_replace(['.', '-'], '', $clienteData['NR_CARTEIRINHA']) : '';
        $qtdFaturas = count($data['Data']) - 1;

        echo "ID Documento (CPF ou CNPJ): " . $idDocumento . PHP_EOL;
        echo "DT_NASC: " . $dtNasc . PHP_EOL;
        echo "NR_PROTOCOLO: " . $nrProtocolo . PHP_EOL;
        echo "NR_CARTEIRINHA: " . $nrCarteirinha . PHP_EOL;
        echo "QTD_FATURAS: " . $qtdFaturas . PHP_EOL;

        $nrTitulo = [];
        $nrSaldoTitulo = [];
        $nrDiasAtraso = [];

        for ($i = 1; $i <= $qtdFaturas; $i++) {
            $fatura = $data['Data'][$i];
            $nrTitulo[] = $fatura['NR_TITULO'];
            $nrSaldoTitulo[] = $fatura['VL_SALDO_TITULO'];
            $nrDiasAtraso[] = $fatura['DT_DIAS_ATRASO'];
            echo "NR_TITULO[$i]: " . $nrTitulo[$i - 1] . PHP_EOL;
            echo "VL_SALDO_TITULO[$i]: " . $nrSaldoTitulo[$i - 1] . PHP_EOL;
            echo "DT_DIAS_ATRASO[$i]: " . $nrDiasAtraso[$i - 1] . PHP_EOL;
        }

    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage() . PHP_EOL;
    }
}

// Verificar se o número de telefone foi passado como argumento
if ($argc < 2) {
    echo "Uso: php arrayTesteUsuarios.php <numero_telefone>" . PHP_EOL;
    exit(1);
}

$nrTelefone = $argv[1];
buscarDadosCliente($nrTelefone);
?>
