<style>
    .diff {
        width: 100%;
        font-family: Consolas, 'Courier New', Courier, monospace;
        font-size: 11px;
    }

    .diff td {
        white-space: pre;
    }

    .diff span {
        padding: 1px;
        display: block;
    }

    .diffUnmodified {
        color: black;
    }

    .diffDeleted {
        background: rgb(255, 224, 224);
        color: red;
    }

    .diffInserted {
        background: greenyellow;
        color: green;
    }

    .diffDeleted, .diffInserted {
        font-weight: bold;
    }
</style>

<?= $content; ?>