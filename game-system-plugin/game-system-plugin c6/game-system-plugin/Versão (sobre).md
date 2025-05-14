Mudança de onde está o arquivo ''match-shortcode'', para dentro da pasta de sistema de filas, porque faz parte desse sistema.

Integração da barra de progresso a nossa tela de fila



----

// Verifica o nonce para segurança
    check_ajax_referer('queue_system_nonce', 'nonce');
mudou isso
era game_system_nonce

Criou arquivo database para todas as tabelas do nosso plug-in

Análise de Funções Redundantes nos Arquivos queue-manager.php e class-queue-system.php

Após revisar os arquivos queue-manager.php e class-queue-system.php, identifiquei algumas funções que possuem responsabilidades semelhantes ou sobrepostas. Isso pode causar confusão, dificultar a manutenção e introduzir bugs no sistema.

---

1. Estrutura Geral dos Arquivos
`queue-manager.php`
- Este arquivo parece ser responsável por interagir diretamente com o banco de dados para gerenciar filas.
- Ele utiliza o `$wpdb` para realizar operações CRUD (Create, Read, Update, Delete) diretamente na tabela de filas.

`class-queue-system.php`
- Este arquivo implementa uma classe `QueueSystem` que gerencia as filas em memória e sincroniza os dados com o banco de dados usando as opções do WordPress (`get_option` e `update_option`).
- Ele também possui lógica adicional para gerenciar partidas e logs.

---

2. Funções Redundantes ou Sobrepostas

Função: `getQueues()`

- **Definição no queue-manager.php:
  ```php
  public function getQueues() {
      global $wpdb;
      $results = $wpdb->get_results("SELECT * FROM {$this->table_name}", ARRAY_A);

      $queues = [];
      foreach ($results as $row) {
          $queues[$row['id']] = maybe_unserialize($row['user_ids']);
      }

      return $queues;
  }
  ```

Definição no class-queue-system.php:
  ```php
  public function getQueues() {
      return $this->queues;
  }
  ```

- **Problema:**
  - A função no queue-manager.php busca as filas diretamente do banco de dados.
  - A função no class-queue-system.php retorna as filas armazenadas em memória (`$this->queues`), que são carregadas a partir de uma opção do WordPress (`get_option('game_system_queues')`).

- **Impacto:**
  - Se as filas em memória (`$this->queues`) não estiverem sincronizadas com o banco de dados, os dois métodos podem retornar resultados diferentes.
  - Isso pode causar inconsistências no sistema.

- **Solução Recomendada:**
  - Centralizar a lógica de recuperação de filas em um único local. Por exemplo:
    - Use o queue-manager.php para gerenciar todas as interações com o banco de dados.
    - O class-queue-system.php pode delegar a recuperação de filas para o `QueueManager`.

---
Função: `saveQueue()`

Definição no queue-manager.php:
  ```php
  public function saveQueue($queueId, $userIds) {
      global $wpdb;
      $wpdb->replace($this->table_name, [
          'id' => $queueId,
          'queue_name' => "Fila {$queueId}",
          'user_ids' => maybe_serialize($userIds),
      ]);
  }
  ```

- **Definição no class-queue-system.php:**
  ```php
  public function setQueues($queues) {
      $this->queues = $queues;
      update_option('game_system_queues', $this->queues);
  }
  ```
Problema:
  - A função no queue-manager.php salva as filas diretamente no banco de dados.
  - A função no class-queue-system.php salva as filas como uma opção do WordPress.

Impacto:
  - Se ambas as funções forem usadas em diferentes partes do código, as filas podem ser salvas em locais diferentes (banco de dados vs. opções do WordPress), causando inconsistências.

Solução Recomendada:
  - Escolha um único método para salvar as filas:
    - Se o banco de dados for o local principal, use o queue-manager.php para salvar as filas.
    - Caso contrário, use o class-queue-system.php para gerenciar as filas como opções do WordPress.

---

Função: `deleteQueue()`

Definição no queue-manager.php:
  ```php
  public function deleteQueue($queueId) {
      global $wpdb;
      $wpdb->delete($this->table_name, ['id' => $queueId]);
  }
  ```
Definição no class-queue-system.php:
  ```php
  public function leaveQueue($userId, $queueId) {
      if (!isset($this->queues[$queueId])) {
          return "A fila {$queueId} não existe.";
      }

      if (!in_array($userId, $this->queues[$queueId])) {
          return "Você não está na fila {$queueId}.";
      }

      $this->queues[$queueId] = array_diff($this->queues[$queueId], [$userId]);

      if (empty($this->queues[$queueId])) {
          unset($this->queues[$queueId]);
      }

      $this->setQueues($this->queues);
      return "Você saiu da fila {$queueId}.";
  }
  ```

Problema:
  - A função no queue-manager.php exclui a fila diretamente do banco de dados.
  - A função no class-queue-system.php remove um usuário da fila e exclui a fila da memória se ela estiver vazia.

Impacto:
  - Se a fila for excluída no banco de dados, mas não na memória, ela pode continuar existindo no sistema até que a memória seja sincronizada.

Solução Recomendada:
  - Centralizar a lógica de exclusão de filas no queue-manager.php e garantir que o class-queue-system.php chame essa função ao excluir filas.

---

3. Resumo dos Problemas

Inconsistência de Dados:
  - As filas podem ser armazenadas em dois locais diferentes (banco de dados e opções do WordPress), causando inconsistências.
Duplicação de Lógica:
  - Funções semelhantes existem em ambos os arquivos, o que aumenta a complexidade e o risco de bugs.
Falta de Centralização:
  - Não há um único ponto de verdade para gerenciar filas, o que dificulta a manutenção.

--
4. Solução Recomendada
Centralizar a Lógica no queue-manager.php

- Use o queue-manager.php para todas as operações relacionadas a filas (CRUD).
- O class-queue-system.php deve delegar essas operações ao `QueueManager`.


No queue-manager.php:
  - Nenhuma alteração necessária, pois ele já gerencia as filas diretamente no banco de dados.

---

5. Benefícios da Refatoração

Centralização:
   - Todas as operações de filas são gerenciadas em um único local (`queue-manager.php`).

Consistência:
   - Garante que as filas estejam sempre sincronizadas entre o banco de dados e a memória.

Manutenção Simplificada:
   - Reduz a duplicação de código e facilita a depuração.

Escalabilidade:
   - Permite adicionar novas funcionalidades relacionadas a filas sem duplicar lógica.

---

Se precisar de ajuda para implementar essas mudanças ou ajustar algo, é só avisar!










--------

Comandos:

Problema:

A função no queue-manager.php busca as filas diretamente do banco de dados.
A função no class-queue-system.php retorna as filas armazenadas em memória ($this->queues), que são carregadas a partir de uma opção do WordPress (get_option('game_system_queues')).
Impacto:

Se as filas em memória ($this->queues) não estiverem sincronizadas com o banco de dados, os dois métodos podem retornar resultados diferentes.
Isso pode causar inconsistências no sistema.
Solução Recomendada:

Centralizar a lógica de recuperação de filas em um único local. Por exemplo:
Use o queue-manager.php para gerenciar todas as interações com o banco de dados.
O class-queue-system.php pode delegar a recuperação de filas para o QueueManager.



-----------


Problema:

A função no queue-manager.php salva as filas diretamente no banco de dados.
A função no class-queue-system.php salva as filas como uma opção do WordPress.
Impacto:

Se ambas as funções forem usadas em diferentes partes do código, as filas podem ser salvas em locais diferentes (banco de dados vs. opções do WordPress), causando inconsistências.
Solução Recomendada:

Escolha um único método para salvar as filas:
Se o banco de dados for o local principal, use o queue-manager.php para salvar as filas.
Caso contrário, use o class-queue-system.php para gerenciar as filas como opções do WordPress.

------------


Problema:

A função no queue-manager.php exclui a fila diretamente do banco de dados.
A função no class-queue-system.php remove um usuário da fila e exclui a fila da memória se ela estiver vazia.
Impacto:

Se a fila for excluída no banco de dados, mas não na memória, ela pode continuar existindo no sistema até que a memória seja sincronizada.
Solução Recomendada:

Centralizar a lógica de exclusão de filas no queue-manager.php e garantir que o class-queue-system.php chame essa função ao excluir filas.


------------

Solução Recomendada
Centralizar a Lógica no queue-manager.php
Use o queue-manager.php para todas as operações relacionadas a filas (CRUD).
O class-queue-system.php deve delegar essas operações ao QueueManager.


---------------

PRECISA ADICIONAR A TABELA DE PARTIDAS