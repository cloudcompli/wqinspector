# Dynamic Patterns — Consolidated

Total: 0 dynamic-dispatch / eval / reflection patterns.

## Summary

No `eval`, `create_function`, variable-variables (`$$x`), variable method/class names,
`call_user_func` with dynamic targets, or reflection were found in any of the 14 analyzed files.

The codebase uses PHP closures extensively (option scoping in `OptionsTrait::withOptions`,
chunk accumulation in `SocrataDataset::getForEachChunk`, filter callbacks in `FileDataset::filter`,
and the `array_map` in `StormwaterViolations::compileWhere`). **All closures are lexically
defined and statically resolvable** — they are direct-invocation callbacks, not dynamic dispatch,
and do not obstruct static call-graph construction.

`__METHOD__` appears in exception messages (OCPW/SMARTS `ParameterDataset`) — a magic constant,
not dynamic dispatch.

## Note on the SOQL string interpolation

The `compileWhere()` string interpolation flagged as a security finding is a **dynamic data
pattern (unescaped query construction)**, not a dynamic code-execution pattern. It is documented
under Security (consolidated-report.md P0) rather than here, because it does not affect call-graph
resolvability.
