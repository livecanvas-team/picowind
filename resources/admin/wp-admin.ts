import * as csstree from 'css-tree';

const links = document.querySelectorAll('link[rel="stylesheet"][href*="wp-admin/load-styles.php"], link[rel="stylesheet"][href*="wp-admin/css/colors/"]')

// reverse the links to load the last one first
Array.from(links).reverse().forEach(link => {
    if (link instanceof HTMLLinkElement) {
        fetch(link.href)
            .then(res => res.text())
            .then(css => {
                const style = document.createElement('style')
                style.textContent = dontTouchMe(css);
                document.head.prepend(style)
                link.remove()
            })
    }
})

function dontTouchMe(css: string) {
    const ast = csstree.parse(css);

    csstree.walk(ast, {
        enter: (node: csstree.CssNode) => {
            if (node.type === 'Atrule' && node.name === 'keyframes') {
                return csstree.walk.skip;
            }

            if (node.type === 'SelectorList') {
                const selectorList = node as csstree.SelectorList;

                selectorList.children.forEach(selectorNode => {
                    const selector = selectorNode as csstree.Selector;

                    // if not the following Pseudo classes are present, skip
                    if (selector.children.some((child: csstree.CssNode) => child.type === 'PseudoClassSelector' && !['visible', 'hover', 'focus', 'focus-visible', 'focus-within', 'target', 'read-write', 'active', 'visited', 'link'].includes(child.name))) {
                        return;
                    }

                    // add :not(#wpbody *)
                    selector.children.push({
                        type: 'PseudoClassSelector',
                        name: 'not',
                        children: [
                            {
                                type: 'ClassSelector',
                                name: 'picowind-style'
                            },
                            {
                                type: 'Combinator',
                                name: ' '
                            },
                            {
                                type: 'TypeSelector',
                                name: '*'
                            }
                        ]
                    } as unknown as csstree.CssNode)
                });
            }
        }
    });

    return csstree.generate(ast);
}

// document.body.classList.add('folded');

const wpbody = document.querySelector('#wpbody');
if (wpbody) {
    wpbody.classList.add('picowind-style');
}

// Keep WordPress admin styles from leaking into Base UI portals.
const observer = new MutationObserver((mutationsList) => {
    for (const mutation of mutationsList) {
        if (mutation.type === 'childList' && mutation.addedNodes.length) {
            mutation.addedNodes.forEach(node => {
                if (
                    node instanceof HTMLElement
                    && !node.closest('#wpbody')
                    && (
                        node.matches('[data-slot], [data-base-ui-portal]')
                        || Boolean(node.querySelector('[data-slot], [data-base-ui-portal]'))
                    )
                ) {
                    node.classList.add('picowind-style')
                }
            })
        }
    }
})
observer.observe(document.body, { childList: true, subtree: false })
