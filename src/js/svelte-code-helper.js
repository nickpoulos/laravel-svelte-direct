const html2 = require("htmlparser2");

exports.findSvelteProps = (svelteCode) => /(?<=export ).+?((?= =)|(?==)|(?=;))/g[Symbol.match](svelteCode)?.map((match) => match.replace(/let |const |var /g, '')) ?? [];
exports.findSvelteTagName = (svelteCode) => {
    let tag = null;
    const parser = new html2.Parser({
        onopentag(name, attributes) {
            if (name.toLowerCase() === 'svelte:options') {
                tag = attributes?.tag.trim() || null
            }
        }
    });

    parser.write(svelteCode);
    parser.end();
    return tag;
};

