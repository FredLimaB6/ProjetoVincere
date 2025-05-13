<?php

class GameSystem {
    private $currentGame;
    private $playerScores = [];
    private $queues = []; // Gerencia múltiplas filas
    private $currentMatches = []; // Gerencia partidas simultâneas
    private $votes = [];
    private $playerElo = [];
    private $monthlyScores = [];
    private $logs = []; // Armazena logs de atividades

    public function __construct() {
        $this->queues = get_option('game_system_queues', []); // Recupera as filas do banco de dados
        $this->currentMatches = get_option('game_system_current_matches', []); // Recupera as partidas do banco de dados
        $this->playerScores = get_option('game_system_player_scores', []);
        $this->playerElo = get_option('game_system_player_elo', []);
        $this->monthlyScores = get_option('game_system_monthly_scores', []);
        $this->logs = get_option('game_system_logs', []); // Recupera os logs do banco de dados
    }

    public function __destruct() {
        // Salva os dados no banco de dados ao finalizar
        update_option('game_system_queues', $this->queues);
        update_option('game_system_current_matches', $this->currentMatches);
        update_option('game_system_player_scores', $this->playerScores);
        update_option('game_system_player_elo', $this->playerElo);
        update_option('game_system_monthly_scores', $this->monthlyScores);
        update_option('game_system_logs', $this->logs);
    }

    public function logActivity($message) {
        $timestamp = date('Y-m-d H:i:s');
        $this->logs[] = "[{$timestamp}] {$message}";
    }

    public function getLogs() {
        return $this->logs;
    }

    public function getQueues() {
        return $this->queues; // Retorna todas as filas ativas
    }

    public function setQueues($queues) {
        $this->queues = $queues; // Atualiza a propriedade $queues com os novos dados
        update_option('game_system_queues', $this->queues); // Salva os dados atualizados no banco de dados
    }

    public function isGameActive() {
        return $this->currentGame !== null; // Retorna true se houver um jogo ativo
    }

    public function getScores() {
        return $this->playerScores; // Retorna as pontuações gerais dos jogadores
    }

    public function getMonthlyScores() {
        return $this->monthlyScores; // Retorna as pontuações mensais dos jogadores
    }

    public function joinQueue($userId) {
        // Verifica se o jogador já está em alguma fila
        foreach ($this->queues as $queueId => $queue) {
            if (in_array($userId, $queue)) {
                return "Você já está na fila {$queueId}.";
            }
        }

        // Adiciona o jogador à primeira fila disponível com menos de 10 jogadores
        foreach ($this->queues as $queueId => $queue) {
            if (count($queue) < 10) {
                $this->queues[$queueId][] = $userId;

                // Log de atividade
                $this->logActivity("Jogador ID {$userId} entrou na fila {$queueId}.");

                // Verifica se a fila está completa
                if (count($this->queues[$queueId]) === 10) {
                    $this->startMatch($queueId);
                }

                return "Você entrou na fila {$queueId}.";
            }
        }

        // Se nenhuma fila disponível, cria uma nova fila
        $newQueueId = count($this->queues) + 1;
        $this->queues[$newQueueId] = [$userId];

        // Log de atividade
        $this->logActivity("Jogador ID {$userId} criou e entrou na nova fila {$newQueueId}.");

        return "Você entrou na nova fila {$newQueueId}.";
    }

    public function startMatch($queueId) {
        if (!isset($this->queues[$queueId]) || count($this->queues[$queueId]) < 10) {
            return "A fila {$queueId} não está pronta para iniciar uma partida.";
        }

        // Cria uma nova partida com os jogadores da fila
        $this->currentMatches[$queueId] = [
            'players' => $this->queues[$queueId],
            'map' => $this->getRandomMap(),
            'teams' => $this->splitTeams($this->queues[$queueId]),
        ];

        // Log de atividade
        $this->logActivity("Partida iniciada na fila {$queueId}.");

        // Remove a fila após iniciar a partida
        unset($this->queues[$queueId]);
    }

    public function voteForWinner($userId, $team) {
        if (!$this->isGameActive()) {
            return "Nenhuma partida ativa no momento. Não é possível votar.";
        }

        if (!in_array($team, ['GR', 'BL'])) {
            return "Time inválido. Escolha entre 'GR' ou 'BL'.";
        }

        if (!isset($this->votes[$team])) {
            $this->votes[$team] = [];
        }

        if (in_array($userId, $this->votes['GR']) || in_array($userId, $this->votes['BL'])) {
            return "Você já votou nesta partida.";
        }

        if (!get_userdata($userId)) {
            return "Usuário inválido.";
        }

        $this->votes[$team][] = $userId;

        // Log de atividade
        $this->logActivity("Jogador ID {$userId} votou no time {$team}.");

        if (count($this->votes[$team]) >= 6) {
            $this->endMatch($team);
            return "O time $team venceu automaticamente com 6 votos!";
        }

        return "Voto registrado com sucesso para o time $team.";
    }

    private function getRandomMap() {
        $maps = ['Mapa 1', 'Mapa 2', 'Mapa 3', 'Mapa 4'];
        return $maps[array_rand($maps)];
    }

    private function splitTeams($players) {
        shuffle($players);
        return [
            'GR' => array_slice($players, 0, 5),
            'BL' => array_slice($players, 5, 5),
        ];
    }

    public function getCurrentMatch() {
        return $this->currentMatches;
    }

    public function getPlayerLevel($elo) {
        if ($elo >= 2001) return 10;
        if ($elo >= 1751) return 9;
        if ($elo >= 1531) return 8;
        if ($elo >= 1351) return 7;
        if ($elo >= 1201) return 6;
        if ($elo >= 1051) return 5;
        if ($elo >= 901) return 4;
        if ($elo >= 751) return 3;
        if ($elo >= 501) return 2;
        return 1;
    }
}
?>