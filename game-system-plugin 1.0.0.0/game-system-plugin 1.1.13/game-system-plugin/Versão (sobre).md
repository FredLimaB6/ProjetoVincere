Nova versão 1.0.0.2 com sistema de lobby:

Quando adicionamos o sistema de lobby, a gente tinha perdido a função de entrar na fila e o fallback também

Descrição do Problema
O sistema de filas apresentava um problema em que, ao clicar no botão "Sair da Fila", o estado da fila não 
era atualizado automaticamente no frontend. Isso causava inconsistências na interface do usuário, já que o 
jogador permanecia visível na fila até que a página fosse recarregada manualmente.

Causa do Problema
O problema estava relacionado à ausência de um fallback para forçar a atualização do estado da fila após 
a interação do usuário. Embora o sistema já enviasse uma solicitação AJAX para processar a saída da fila, 
não havia um mecanismo para garantir que o estado fosse atualizado no frontend caso a resposta do servidor 
não fosse suficiente.

Solução Implementada
Foi implementado um fallback no arquivo game-system.js para garantir que o estado da fila seja atualizado 
2 segundos após o clique no botão "Sair da Fila". Essa abordagem assegura que o frontend esteja sincronizado 
com o backend, mesmo em casos de atraso ou falha na resposta inicial.

----------------------------------------------------------------------------------------------------------------
Relatório Técnico: Correção do Problema no Botão "Entrar na Fila"
Descrição do Problema
O botão "Entrar na Fila" não estava funcionando corretamente. Ao clicar no botão, nenhuma ação era executada, 
e o estado da fila não era atualizado. Após análise, identificamos que o problema estava relacionado à variável 
pluginDirUrl, que não estava definida no JavaScript. Essa variável é necessária para carregar corretamente 
recursos, como o som de entrada na fila.

Causa do Problema
A variável pluginDirUrl não estava sendo passada do PHP para o JavaScript. Como resultado:

O som de entrada na fila (join-queue.mp3) não era carregado corretamente.
O código JavaScript falhava ao tentar acessar pluginDirUrl, resultando em um erro no console do navegador:
Solução Implementada
Adição de pluginDirUrl no wp_localize_script

A variável pluginDirUrl foi adicionada ao objeto gameSystemAjax no PHP, permitindo que ela seja acessada no 
JavaScript.
Correção no Arquivo game-system-plugin.php

O código foi ajustado para incluir pluginDirUrl no wp_localize_script.

Correção no Arquivo game-system.js

O código JavaScript foi ajustado para usar gameSystemAjax.pluginDirUrl ao carregar o som de entrada na fila.