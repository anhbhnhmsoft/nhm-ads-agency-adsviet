const youtubeIdPattern = /^[A-Za-z0-9_-]{6,}$/;

const htmlEntities: Record<string, string> = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
};

const allowedTags = new Set([
    'p',
    'br',
    'strong',
    'b',
    'em',
    'i',
    'u',
    's',
    'ul',
    'ol',
    'li',
    'h2',
    'h3',
    'h4',
    'blockquote',
    'a',
    'img',
    'figure',
    'figcaption',
    'iframe',
]);

const dangerousTags = new Set(['script', 'style']);

export const escapeHtml = (value: string) =>
    value.replace(/[&<>"']/g, (char) => htmlEntities[char]);

export const escapeHtmlAttribute = escapeHtml;

export const getYouTubeEmbedUrl = (url: string): string | null => {
    try {
        let value = url.trim();
        if (value.startsWith('/embed/') || value.startsWith('/shorts/')) {
            const videoId = value.split('/').filter(Boolean)[1] || '';
            return youtubeIdPattern.test(videoId)
                ? `https://www.youtube.com/embed/${videoId}`
                : null;
        }
        if (
            value.startsWith('youtube.com') ||
            value.startsWith('www.youtube.com') ||
            value.startsWith('youtu.be') ||
            value.startsWith('www.youtu.be')
        ) {
            value = `https://${value}`;
        }

        const parsed = new URL(value);
        const host = parsed.hostname.replace(/^www\./, '');
        let videoId = '';

        if (host === 'youtu.be') {
            videoId = parsed.pathname.split('/').filter(Boolean)[0] || '';
        } else if (
            host === 'youtube.com' ||
            host.endsWith('.youtube.com') ||
            host === 'youtube-nocookie.com' ||
            host.endsWith('.youtube-nocookie.com')
        ) {
            const parts = parsed.pathname.split('/').filter(Boolean);
            if (parts[0] === 'watch') {
                videoId = parsed.searchParams.get('v') || '';
            } else if (parts[0] === 'embed' || parts[0] === 'shorts') {
                videoId = parts[1] || '';
            }
        }

        return youtubeIdPattern.test(videoId)
            ? `https://www.youtube.com/embed/${videoId}`
            : null;
    } catch {
        return null;
    }
};

export const getSafeContentUrl = (url: string): string | null => {
    const value = url.trim();
    if (value === '') return null;
    if (value.startsWith('/') && !value.startsWith('//')) return value;

    try {
        const parsed = new URL(value);
        return parsed.protocol === 'http:' || parsed.protocol === 'https:'
            ? value
            : null;
    } catch {
        return null;
    }
};

const sanitizeChildren = (source: Node, doc: Document) => {
    const fragment = doc.createDocumentFragment();
    source.childNodes.forEach((child) => {
        const sanitized = sanitizeNode(child, doc);
        if (sanitized) fragment.append(sanitized);
    });
    return fragment;
};

const sanitizeNode = (node: Node, doc: Document): Node | null => {
    if (node.nodeType === Node.TEXT_NODE) {
        return doc.createTextNode(node.textContent || '');
    }

    if (node.nodeType !== Node.ELEMENT_NODE) {
        return null;
    }

    const source = node as Element;
    const originalTag = source.tagName.toLowerCase();
    if (dangerousTags.has(originalTag)) {
        return null;
    }

    const tag = originalTag === 'div' ? 'p' : originalTag;
    if (!allowedTags.has(tag)) {
        return sanitizeChildren(source, doc);
    }

    if (tag === 'br') {
        return doc.createElement('br');
    }

    if (tag === 'img') {
        const src = getSafeContentUrl(source.getAttribute('src') || '');
        if (!src) return null;

        const image = doc.createElement('img');
        image.setAttribute('src', src);
        image.setAttribute('alt', source.getAttribute('alt') || '');
        image.setAttribute('loading', 'lazy');
        return image;
    }

    if (tag === 'iframe') {
        const src = getYouTubeEmbedUrl(source.getAttribute('src') || '');
        if (!src) return null;

        const iframe = doc.createElement('iframe');
        iframe.setAttribute('src', src);
        iframe.setAttribute(
            'title',
            source.getAttribute('title') || 'Instruction video',
        );
        iframe.setAttribute(
            'allow',
            'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
        );
        iframe.setAttribute('allowfullscreen', '');
        return iframe;
    }

    if (tag === 'a') {
        const href = getSafeContentUrl(source.getAttribute('href') || '');
        if (!href) return sanitizeChildren(source, doc);

        const link = doc.createElement('a');
        link.setAttribute('href', href);
        link.setAttribute('target', '_blank');
        link.setAttribute('rel', 'noopener noreferrer');
        link.append(sanitizeChildren(source, doc));
        return link;
    }

    const element = doc.createElement(tag);
    element.append(sanitizeChildren(source, doc));
    return element;
};

export const sanitizeDashboardGuideHtml = (html: string): string => {
    if (typeof document === 'undefined') return '';

    const parsed = new DOMParser().parseFromString(
        `<div>${html}</div>`,
        'text/html',
    );
    const output = document.createElement('div');
    parsed.body.firstElementChild?.childNodes.forEach((child) => {
        const sanitized = sanitizeNode(child, document);
        if (sanitized) output.append(sanitized);
    });

    return output.innerHTML.trim();
};

export const createImageFigureHtml = (src: string, alt = '', caption = '') => {
    const safeSrc = getSafeContentUrl(src);
    if (!safeSrc) return null;

    const captionHtml = caption.trim()
        ? `<figcaption>${escapeHtml(caption.trim())}</figcaption>`
        : '';

    return `<figure><img src="${escapeHtmlAttribute(safeSrc)}" alt="${escapeHtmlAttribute(alt.trim())}">${captionHtml}</figure><p><br></p>`;
};

export const createYouTubeEmbedHtml = (url: string, title = '') => {
    const embedUrl = getYouTubeEmbedUrl(url);
    if (!embedUrl) return null;

    const safeTitle = title.trim() || 'Instruction video';
    const captionHtml = title.trim()
        ? `<figcaption>${escapeHtml(title.trim())}</figcaption>`
        : '';

    return `<figure><iframe src="${escapeHtmlAttribute(embedUrl)}" title="${escapeHtmlAttribute(safeTitle)}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>${captionHtml}</figure><p><br></p>`;
};
