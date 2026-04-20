Feature: Candidatar-se a uma Vaga (Apply to Job)
  Como um Candidato
  Quero me candidatar a uma vaga publicada
  Para que meu perfil seja avaliado pelo recrutador e pelo motor de matching

  Scenario: Candidatura bem-sucedida a vaga aberta
    Given que existe uma vaga publicada com status "published" e accepts_applications = true
    And meu perfil de candidato está completo com currículo ativo na versão 1
    And meu matching_opt_in está habilitado
    When eu submeto minha candidatura para a vaga
    Then minha candidatura deve ser registrada com status "pending_review"
    And o evento "CandidateAppliedToJob" deve ser emitido com candidateId, jobId e appliedAt
    And a vaga deve incrementar seu contador de candidaturas

  Scenario: Falha ao candidatar-se a vaga fechada ou expirada
    Given que existe uma vaga com status "closed"
    And meu perfil está completo
    When eu submeto minha candidatura para a vaga
    Then a candidatura deve ser rejeitada com erro "Vaga não aceita mais candidaturas"
    And nenhum registro de candidatura deve ser criado
    And nenhum evento de candidatura deve ser emitido

  Scenario Outline: Candidatura com diferentes estados de perfil
    Given que existe uma vaga publicada válida
    And meu perfil possui as seguintes características:
      | has_active_resume | matching_opt_in | skills_count |
      | <has_resume>      | <opt_in>        | <skills>     |
    When eu submeto minha candidatura
    Then o resultado deve ser "<expected_status>"
    And a mensagem de resposta deve conter "<expected_message>"

    Examples:
      | has_resume | opt_in | skills | expected_status | expected_message                          |
      | true       | true   | 3      | success         | Candidatura registrada                    |
      | false      | true   | 3      | error           | É necessário anexar um currículo válido   |
      | true       | false  | 3      | error           | Candidaturas desabilitadas por privacidade|