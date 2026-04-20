Feature: Publicar Vaga (Publish Job)
  Como um Recrutador
  Quero publicar uma vaga de emprego
  Para que ela fique visível aos candidatos e disponível para matching

  Scenario Outline: Publicar vaga válida com sucesso
    Given que existe uma vaga no status "draft" com título "<title>" e descrição "<description>"
    And a faixa salarial é <salary_min> até <salary_max> na moeda <currency>
    And o tipo de emprego é "<employment_type>"
    When eu solicito a publicação da vaga
    Then a vaga deve mudar para o status "published"
    And o evento "JobPublished" deve ser emitido com jobId, title e expires_at
    And o campo published_at deve ser preenchido com o timestamp atual
    And expires_at deve ser calculado como published_at + 60 dias

    Examples:
      | title                      | description                                      | salary_min | salary_max | currency | employment_type |
      | Desenvolvedor Laravel Pleno| Responsável pelo backend da plataforma...        | 5000       | 8000       | BRL      | FULL_TIME       |
      | Gerente de Projetos Remoto | Liderança de squads ágeis e entrega de valor...  | 12000      | 18000      | USD      | CONTRACT        |
      | Estagiário de QA           | Execução de testes manuais e automatizados...    | 1500       | 2000       | BRL      | INTERNSHIP      |

  Scenario: Falha ao publicar sem título válido
    Given que existe uma vaga no status "draft" com título "Dev"
    And a descrição está preenchida corretamente com mais de 50 caracteres
    When eu solicito a publicação da vaga
    Then a publicação deve falhar com erro "O título deve ter entre 5 e 100 caracteres"
    And o status da vaga deve permanecer "draft"
    And nenhum evento "JobPublished" deve ser emitido

  Scenario: Falha ao publicar com faixa salarial inconsistente
    Given que existe uma vaga no status "draft" com título válido e descrição válida
    And a faixa salarial é 10000 até 5000 na moeda BRL
    When eu solicito a publicação da vaga
    Then a publicação deve falhar com erro "O salário mínimo não pode ser maior que o máximo"
    And o status da vaga deve permanecer "draft"
    And o aggregate não deve registrar alterações no banco de dados