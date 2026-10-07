// Conventional Commits (decision record 25): `type(scope): subject`, type in English, subject and body in
// Russian (so no subject-case rule), scope = change name, `agent` for instructions, none for chore work.
// Rules are inline: only @commitlint/cli is installed (mise.toml), no shared config package.
export default {
  rules: {
    'type-enum': [2, 'always', ['feat', 'fix', 'docs', 'chore', 'refactor', 'test', 'ci', 'build', 'perf', 'revert']],
    'type-empty': [2, 'never'],
    'type-case': [2, 'always', 'lower-case'],
    'scope-case': [2, 'always', 'kebab-case'],
    'subject-empty': [2, 'never'],
    'subject-full-stop': [2, 'never', '.'],
    'header-max-length': [2, 'always', 100],
    'body-leading-blank': [2, 'always'],
    'footer-leading-blank': [2, 'always'],
  },
};
