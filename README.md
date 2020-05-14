# PMHopper
A PocketMine-MP plugin implementing vanilla hopper behaviour.

## Optimizations
A hopper will continue to tick itself every `transfer-cooldown` (default: 8) ticks only if there is a container above it or on the side it's tube is facing.<br>
When not in use, a hopper (or more precisely, it's behaviour) per se will NEVER contribute to any additional server load.<br>
However, dropped items check for hoppers below them every `items-sucking-tick-rate` (default: 1) tick(s).

Reconfiguring some variables may help reduce server load. For example, the default configuration values for `transfer-cooldown` and `items-sucked` reflect vanilla.
```yaml
transfer-cooldown: 8
items-sucked: 1
```
The default configuration tries every 8 ticks to sucks 1 item from a container above it. Changing it to suck 3 items every 24 ticks will decrease the number of times a hopper gets ticked.
