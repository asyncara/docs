# Asyncara developer documentation

English documentation for application developers using the Asyncara PHP runtime.

- [Read the documentation](https://asyncara.github.io/docs/)
- [Install Asyncara](https://asyncara.github.io/docs/guide/installation/)
- [Run the examples](https://asyncara.github.io/docs/guide/reference/examples/)
- [Browse the API](https://asyncara.github.io/docs/guide/reference/overview/)

The runtime remains EXPERIMENTAL. Check the platform and feature requirements in each guide.

## Contribute to the documentation

This independent repository contains the Docusaurus site and runnable PHP examples. The published site uses English only. Existing Arabic translation source is retained but excluded from the site and search index.

Use Node.js 22 and npm:

```sh
npm ci
npm start
```

Run `npm run check:content`, `npm run check:examples -- --lint-only` and `npm run build` before submitting a change. The build rejects broken links. Local search is generated during the build.

PHP examples live in `examples/` and run through the installed `asyncara` launcher. Run `python3 scripts/check_examples.py --help` for contributor validation options. Public guides explain application usage; internal engineering reports and repository administration instructions do not belong in the site.
