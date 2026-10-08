import { z } from 'zod'

// Our CSP forbids eval. Without `jitless`, zod probes for it with `new Function`, which the browser reports as a
// CSP violation on every load. Imported first in main.tsx: ES imports run before the importing module's body.
z.config({ jitless: true })
