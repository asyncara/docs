# Asyncara developer documentation

English-first Docusaurus documentation, with an Arabic RTL translation for the Asyncara PHP runtime. Published at
https://asyncara.github.io/docs/ from this independent repository.

Requires Node.js22 and npm. Run `npm ci`, `npm start`, or `npm run build`.
The production build rejects broken links. Local search is generated at build time;
no external search service or analytics account is required.

Runnable PHP examples live in `examples/`. Use an installed `asyncara` launcher,
or set `ASYNCARA_AUTOLOAD` and load a matching extension explicitly. Run
`python3 scripts/check_examples.py --help` for validation commands. Reference
signatures are generated from source; guides explain lifecycle and usage.

The runtime remains EXPERIMENTAL. The docs distinguish 0.10.0, later Android
preview packages, and unavailable features. They contain no private engineering
logs, workstation manifests or credentials. Runtime code is in asyncara/asyncara.
