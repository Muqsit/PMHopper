# PMHopper
A PocketMine-MP plugin implementing vanilla hopper behaviour.

## Optimizations
A hopper will continue to tick itself every `transfer.tick-rate` (default: 8) game ticks only if there is a container above it or on the side it's tube is facing.<br>
When not in use, a hopper (or more precisely, it's behaviour) per se will NEVER contribute to any additional server load.<br>
However, (`item-sucking.per-tick` number of) dropped items check for hoppers below them every `item-sucking.tick-rate` (default: 1) game tick(s).

Reconfiguring some variables may help reduce server load. The default configuration values for `transfer.tick-rate` and `transfer.per-tick` reflect vanilla.
Tripling `transfer.tick-rate` as well as `transfer.per-tick` would reduce the frequency at which containers are scanned by 3x at the cost of transfers getting delayed by 3x.
```yaml
# Optimization: instead of transferring 1 item every 8 ticks, transfer 3 items every 24 ticks.
transfer:
  tick-rate: 24
  per-tick: 3
```
