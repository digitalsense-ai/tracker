<!DOCTYPE html>
<html>
<head>
    <title>Profiles Tools</title>
    <style>
        body { font-family: sans-serif; margin: 2em; background:#f9fafb; }
        textarea { width:100%; height:200px; }
        label { display:block; margin-top:1em; }
    </style>
</head>
<body>
    <h1>Profiles Tools</h1>
    <form method="POST" action="/profiles/tools/run">
        <?php echo csrf_field(); ?>
        <label>Days <input type="number" name="days" value="5"></label>
        <label>Limit <input type="number" name="limit" value="50"></label>
        <label>Profile ID (optional) <input type="number" name="profile"></label>
        <button type="submit">Run</button>
    </form>
    <?php if(isset($output)): ?>
        <h2>Output</h2>
        <textarea readonly><?php echo $output; ?></textarea>
        <h2>Counts</h2>
        <pre><?php print_r($counts); ?></pre>
    <?php endif; ?>
</body>
</html>
