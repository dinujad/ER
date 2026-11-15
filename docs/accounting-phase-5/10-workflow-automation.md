# Workflow Automation

`WorkflowAutomationService` allowlists triggers/actions. Executions unique on `occurrence_key`.

Actions never post journals (`gl_posted=false`). Schedule: `accounting:process-automation` daily 02:15.
