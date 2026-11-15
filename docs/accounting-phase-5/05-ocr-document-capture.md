# OCR Document Capture

Service: `OcrExtractionService` + `ProcessOcrDocumentJob`.

Pipeline: upload → validate mime → private store → extract → normalize → validate amounts (subtotal−discount+tax=total) → duplicate fingerprint/invoice → review task → optional draft (not posted).

Without live OCR model, fake driver does not invent fields.

LKR only; foreign currency flagged / draft blocked.
