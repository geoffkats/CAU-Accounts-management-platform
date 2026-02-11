# Accounting Controls Roadmap

This roadmap lists recommended enhancements to move from solid double-entry foundations to stricter, audit-ready operations.

## Period Controls
- Period lock settings (e.g., monthly close dates)
- Enforcement in posting/voiding/mutation endpoints
- Admin override with audit reason capture

## Approval Workflow
- States: draft → approved → posted
- Role-based approval (e.g., Preparer vs Approver)
- Approval logs (who, when, reason)

## Replace-on-Edit Policy
- Editing posted entries creates a replacement entry
- Option A: Void original; Option B: Post reversing entry + corrected entry
- Link via `replaces_entry_id`; surface in UI badges

## Enumerations & Validation
- Enforce `type` and `status` as enums (validation + DB CHECK constraints if supported)
- FormRequest validation for create/update of entries and lines
- Prevent zero-line or zero-amount entries

## Closing & Opening Helpers
- Guided closing entry creation (temporary → retained earnings)
- Opening balances for new fiscal year

## Reporting Integrity
- Reports only include `posted` entries
- Clear labeling for voided/replaced in detailed views
- Base-currency posting with presentational conversions

## Testing & QA
- Unit tests: create/post/void/replace workflows
- Property tests: random lines always balanced on creation
- Feature tests: index filters, show badges, audit log integrity

## Audit Enhancements
- Hash-chaining audit entries (already started)
- Capture IP, user-agent, and route in audit log
- Exportable audit trail for regulators

## Performance & Scale
- Composite indexes on `date`, `status`, `type`
- Pagination defaults and limits
- Background jobs for heavy adjustments or imports

## Security & Permissions
- Fine-grained abilities (post, void, approve, close period)
- Admin-only overrides with reason codes
