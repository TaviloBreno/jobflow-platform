# ADR-XXX: Título da Decisão

## Status
[Proposto | Aceito | Deprecado | Substituído]

## Contexto
Descreva o problema ou necessidade que levou a esta decisão.

## Decisão
Qual solução foi escolhida e por quê.

## Consequências
### Positivas
- Benefício 1
- Benefício 2

### Negativas
- Trade-off 1
- Trade-off 2

## Alternativas Consideradas
1. Alternativa A (motivo da rejeição)
2. Alternativa B (motivo da rejeição)

## Referências
- Link ou referência técnica
'@ | Out-File -FilePath "docs/adr/adr-template.md" -Encoding utf8 -Force

# Criar arquivos vazios para os 5 ADRs
1..5 | ForEach-Object {
    $num = $_.ToString("D2")
    Out-File -FilePath "docs/adr/ADR-00${num}-template.md" -Encoding utf8 -Force
}