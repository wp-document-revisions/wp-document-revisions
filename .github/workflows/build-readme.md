name: Update README

on:
  push:
    branches:
      - main
    paths:
      - "docs/*.md"
  workflow_dispatch: {}

jobs:
  build readme:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v2
      
      - name: Setup Ruby
        uses: ruby/setup-ruby@v1

      - name: Build readme
        run: script/build-readme

      - name: Create Pull Request
        uses: peter-evans/create-pull-request@v3
        with:
            commit-message: Update README
            branch: update-pull-request/readme
            title: Update README
