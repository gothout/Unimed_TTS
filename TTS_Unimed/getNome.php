<?php
class ClienteNomeFetcher
{
    private $host = 'localhost';
    private $port = '5432';
    private $dbname = 'xcontact';
    private $user = 'xcontact';
    private $password = 'ajx52l15';
    private $conn;

    public function __construct()
    {
        // Cria a conexão com o banco de dados PostgreSQL
        $this->conn = pg_connect("host={$this->host} port={$this->port} dbname={$this->dbname} user={$this->user} password={$this->password}");

        if (!$this->conn) {
            throw new Exception("Erro ao conectar ao banco de dados.");
        }
    }

    public function getNomeCliente($numero)
    {
        // Verifica se o número foi fornecido
        if (empty($numero)) {
            throw new InvalidArgumentException("Número não fornecido.");
        }

        // Consulta para obter o id_contato associado ao número
        $query1 = 'SELECT "id_contato" FROM "public"."campanhas_contato_numeros" WHERE "numero" = $1';
        $result1 = pg_query_params($this->conn, $query1, array($numero));

        if (!$result1) {
            $error = pg_last_error($this->conn);
            throw new RuntimeException("Erro na primeira consulta: $error");
        }

        // Verifica se há resultados na primeira consulta
        if ($row1 = pg_fetch_assoc($result1)) {
            $id_contato = $row1['id_contato'];

            // Consulta para obter o nome associado ao id_contato
            $query2 = 'SELECT "valor" FROM "public"."campanhas_contato_campos" WHERE "id_contato" = $1';
            $result2 = pg_query_params($this->conn, $query2, array($id_contato));

            if (!$result2) {
                $error = pg_last_error($this->conn);
                throw new RuntimeException("Erro na segunda consulta: $error");
            }

            // Verifica se há resultados na segunda consulta
            if ($row2 = pg_fetch_assoc($result2)) {
                return htmlspecialchars($row2['valor']);
            } else {
                throw new RuntimeException("Nenhum nome encontrado para o id_contato fornecido.");
            }
        } else {
            throw new RuntimeException("Nenhum id_contato encontrado para o número fornecido.");
        }
    }

    public function __destruct()
    {
        // Fecha a conexão com o banco de dados
        if ($this->conn) {
            pg_close($this->conn);
        }
    }
}
?>
