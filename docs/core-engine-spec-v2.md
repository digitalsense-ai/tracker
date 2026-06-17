# Tracker Core Engine Spec
**Version: v2**

(Updated with clean exit logic)

## Exit Logic (Canonical Behavior)

The exit system follows a two-phase model.

### Phase 1: Initial trade protection
- Stop-loss set
- Risk calculated
- Target = entry + (risk * take_profit_rr)

### Phase 2: Exit behavior

#### Trailing OFF
- Stop hit → exit loss
- Target hit → exit profit

#### Trailing ON
- Target becomes trigger
- Activate trailing
- Move stop to break-even

### Phase 3: Trailing
- Stop only moves upward
- Exit when trailing stop hit

## Rule
TP = exit OR trigger depending on trailing

## Decision
Use take_profit_rr
Do NOT use tp_levels in core
