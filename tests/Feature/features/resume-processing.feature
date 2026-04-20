Feature: Processamento de Currículos (Resume Processing)
  Como um Sistema de Processamento
  Quero extrair informações de currículos enviados
  Para enriquecer o perfil do candidato e alimentar o motor de matching

  Scenario: Parse bem-sucedido de currículo em PDF
    Given que um candidato enviou um currículo válido em formato PDF com 2MB
    When o serviço de processamento inicia a extração
    Then o currículo deve ser parseado com sucesso
    And as skills extraídas devem ser salvas no perfil do candidato
    And o evento "ResumeParsedSuccessfully" deve ser emitido com candidateId e extractedSkills
    And o status do currículo no perfil deve mudar para "active"

  Scenario: Falha ao processar arquivo corrompido ou inválido
    Given que um candidato enviou um arquivo corrompido com extensão .pdf
    When o serviço de processamento tenta a extração
    Then o processamento deve falhar com erro "Arquivo corrompido ou formato não suportado"
    And o evento "ResumeParsingFailed" deve ser emitido com candidateId e errorCode
    And o perfil do candidato não deve ser alterado
    And o aggregate de Candidate deve manter a versão anterior do currículo

  Scenario Outline: Validação de formato e tamanho de arquivo
    Given que um candidato tentou enviar um currículo no formato "<file_format>"
    And o tamanho do arquivo é "<file_size_mb>" MB
    When o serviço de validação recebe o arquivo
    Then o resultado da validação deve ser "<validation_result>"
    And a mensagem de erro deve ser "<error_message>"

    Examples:
      | file_format | file_size_mb | validation_result | error_message                              |
      | PDF         | 1.2          | success           | Arquivo aceito para processamento          |
      | DOCX        | 0.8          | success           | Arquivo aceito para processamento          |
      | TXT         | 0.1          | failed            | Formato TXT não suportado                  |
      | PDF         | 15.5         | failed            | Tamanho máximo excedido (limite: 10MB)     |