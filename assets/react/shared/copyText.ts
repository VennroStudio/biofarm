/** Call directly from a click handler so the HTTP fallback keeps user activation. */
export async function copyText(text: string, container: HTMLElement = document.body): Promise<void> {
    if (navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return;
        } catch {
            // Clipboard API may be denied even where it is available.
        }
    }

    // Compatibility fallback for HTTP development hosts without Clipboard API.
    // Keep the temporary field inside the modal to respect its focus trap.
    const previousFocus = document.activeElement;
    const selection = document.getSelection();
    const ranges = selection ? Array.from({ length: selection.rangeCount }, (_, i) => selection.getRangeAt(i).cloneRange()) : [];
    const field = document.createElement('textarea');
    field.value = text;
    field.readOnly = true;
    field.tabIndex = -1;
    field.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:1px;opacity:0;font-size:16px;pointer-events:none';
    container.appendChild(field);
    try {
        field.focus({ preventScroll: true });
        field.select();
        field.setSelectionRange(0, text.length);
        if (!document.execCommand('copy')) throw new Error('Copy command was rejected');
    } finally {
        field.remove();
        if (previousFocus instanceof HTMLElement) previousFocus.focus({ preventScroll: true });
        if (selection) {
            selection.removeAllRanges();
            for (const range of ranges) selection.addRange(range);
        }
    }
}
