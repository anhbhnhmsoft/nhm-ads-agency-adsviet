import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import {
    createImageFigureHtml,
    createYouTubeEmbedHtml,
    escapeHtml,
    escapeHtmlAttribute,
    getSafeContentUrl,
    sanitizeDashboardGuideHtml,
} from '@/lib/dashboard-guide';
import DashboardGuideCard from '@/pages/wallet/components/DashboardGuideCard';
import { Head, useForm } from '@inertiajs/react';
import {
    Bold,
    Heading2,
    Heading3,
    Image,
    Italic,
    Link2,
    List,
    ListOrdered,
    Quote,
    Save,
    Underline,
    Youtube,
} from 'lucide-react';
import {
    type ClipboardEvent,
    type FormEvent,
    type ReactNode,
    useEffect,
    useMemo,
    useRef,
} from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    content?: string | null;
};

type DashboardGuideForm = {
    content: string;
};

type ToolbarButtonProps = {
    label: string;
    children: ReactNode;
    onClick: () => void;
};

const ToolbarButton = ({ label, children, onClick }: ToolbarButtonProps) => (
    <Button
        type="button"
        variant="outline"
        size="icon-sm"
        onMouseDown={(event) => event.preventDefault()}
        onClick={onClick}
        aria-label={label}
        title={label}
    >
        {children}
    </Button>
);

const DashboardGuideConfig = ({ content = '' }: Props) => {
    const { t } = useTranslation();
    const editorRef = useRef<HTMLDivElement>(null);
    const form = useForm<DashboardGuideForm>({
        content: content || '',
    });
    const { data, setData, processing, errors } = form;
    const previewHtml = useMemo(
        () => sanitizeDashboardGuideHtml(data.content),
        [data.content],
    );

    useEffect(() => {
        if (!editorRef.current) return;

        const nextContent = content || '';
        editorRef.current.innerHTML = nextContent;
        setData('content', nextContent);
    }, [content]);

    const syncContent = () => {
        setData('content', editorRef.current?.innerHTML || '');
    };

    const focusEditor = () => {
        editorRef.current?.focus();
        if (typeof document !== 'undefined') {
            document.execCommand('defaultParagraphSeparator', false, 'p');
        }
    };

    const runCommand = (command: string, value?: string) => {
        if (typeof document === 'undefined') return;

        focusEditor();
        document.execCommand(command, false, value);
        syncContent();
    };

    const insertHtml = (html: string) => {
        if (typeof document === 'undefined') return;

        focusEditor();
        document.execCommand('insertHTML', false, html);
        syncContent();
    };

    const alertInvalidUrl = () => {
        window.alert(
            t('dashboard_guide.invalid_url', {
                defaultValue: 'Please enter a valid http(s) or relative URL.',
            }),
        );
    };

    const handleLink = () => {
        const url = window.prompt(
            t('dashboard_guide.link_url', {
                defaultValue: 'Link URL',
            }),
        );
        if (url === null) return;

        const safeUrl = getSafeContentUrl(url);
        if (!safeUrl) {
            alertInvalidUrl();
            return;
        }

        const selectedText = window.getSelection()?.toString();
        if (selectedText) {
            runCommand('createLink', safeUrl);
            return;
        }

        insertHtml(
            `<a href="${escapeHtmlAttribute(safeUrl)}" target="_blank" rel="noopener noreferrer">${escapeHtml(safeUrl)}</a>`,
        );
    };

    const handleImage = () => {
        const src = window.prompt(
            t('dashboard_guide.image_url', {
                defaultValue: 'Image URL',
            }),
        );
        if (src === null) return;

        const alt = window.prompt(
            t('dashboard_guide.image_alt', {
                defaultValue: 'Alt text',
            }),
            '',
        );
        if (alt === null) return;

        const caption = window.prompt(
            t('dashboard_guide.image_caption', {
                defaultValue: 'Caption',
            }),
            '',
        );
        if (caption === null) return;

        const html = createImageFigureHtml(src, alt, caption);
        if (!html) {
            alertInvalidUrl();
            return;
        }

        insertHtml(html);
    };

    const handleYouTube = () => {
        const url = window.prompt(
            t('dashboard_guide.youtube_url', {
                defaultValue: 'YouTube URL',
            }),
        );
        if (url === null) return;

        const title = window.prompt(
            t('dashboard_guide.youtube_title_label', {
                defaultValue: 'Title',
            }),
            t('dashboard_guide.youtube_title', {
                defaultValue: 'Instruction video',
            }),
        );
        if (title === null) return;

        const html = createYouTubeEmbedHtml(url, title);
        if (!html) {
            window.alert(
                t('dashboard_guide.invalid_youtube_url', {
                    defaultValue: 'Please enter a valid YouTube URL.',
                }),
            );
            return;
        }

        insertHtml(html);
    };

    const handlePaste = (event: ClipboardEvent<HTMLDivElement>) => {
        const html = event.clipboardData.getData('text/html');
        if (!html) return;

        event.preventDefault();
        insertHtml(sanitizeDashboardGuideHtml(html));
    };

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();

        const cleanContent = sanitizeDashboardGuideHtml(
            editorRef.current?.innerHTML || data.content,
        );
        if (editorRef.current) {
            editorRef.current.innerHTML = cleanContent;
        }
        setData('content', cleanContent);

        form.transform(() => ({ content: cleanContent }));
        form.put('/config/dashboard-guide', {
            preserveScroll: true,
        });
    };

    return (
        <div className="space-y-6">
            <Head
                title={t('dashboard_guide.admin_title', {
                    defaultValue: 'Dashboard guide',
                })}
            />
            <div>
                <h1 className="text-xl font-semibold">
                    {t('dashboard_guide.admin_title', {
                        defaultValue: 'Dashboard guide',
                    })}
                </h1>
                <p className="text-sm text-muted-foreground">
                    {t('dashboard_guide.admin_description', {
                        defaultValue:
                            'Create customer instructions shown below the wallet transaction history.',
                    })}
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {t('dashboard_guide.content_title', {
                                defaultValue: 'Guide content',
                            })}
                        </CardTitle>
                        <CardDescription>
                            {t('dashboard_guide.content_description', {
                                defaultValue:
                                    'Write rich text, insert image URLs, or embed YouTube videos.',
                            })}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-wrap gap-2">
                            <ToolbarButton
                                label={t('dashboard_guide.toolbar_bold', {
                                    defaultValue: 'Bold',
                                })}
                                onClick={() => runCommand('bold')}
                            >
                                <Bold />
                            </ToolbarButton>
                            <ToolbarButton
                                label={t('dashboard_guide.toolbar_italic', {
                                    defaultValue: 'Italic',
                                })}
                                onClick={() => runCommand('italic')}
                            >
                                <Italic />
                            </ToolbarButton>
                            <ToolbarButton
                                label={t('dashboard_guide.toolbar_underline', {
                                    defaultValue: 'Underline',
                                })}
                                onClick={() => runCommand('underline')}
                            >
                                <Underline />
                            </ToolbarButton>
                            <ToolbarButton
                                label={t('dashboard_guide.toolbar_heading_2', {
                                    defaultValue: 'Heading 2',
                                })}
                                onClick={() => runCommand('formatBlock', 'h2')}
                            >
                                <Heading2 />
                            </ToolbarButton>
                            <ToolbarButton
                                label={t('dashboard_guide.toolbar_heading_3', {
                                    defaultValue: 'Heading 3',
                                })}
                                onClick={() => runCommand('formatBlock', 'h3')}
                            >
                                <Heading3 />
                            </ToolbarButton>
                            <ToolbarButton
                                label={t(
                                    'dashboard_guide.toolbar_bulleted_list',
                                    {
                                        defaultValue: 'Bulleted list',
                                    },
                                )}
                                onClick={() =>
                                    runCommand('insertUnorderedList')
                                }
                            >
                                <List />
                            </ToolbarButton>
                            <ToolbarButton
                                label={t(
                                    'dashboard_guide.toolbar_numbered_list',
                                    {
                                        defaultValue: 'Numbered list',
                                    },
                                )}
                                onClick={() => runCommand('insertOrderedList')}
                            >
                                <ListOrdered />
                            </ToolbarButton>
                            <ToolbarButton
                                label={t('dashboard_guide.toolbar_quote', {
                                    defaultValue: 'Quote',
                                })}
                                onClick={() =>
                                    runCommand('formatBlock', 'blockquote')
                                }
                            >
                                <Quote />
                            </ToolbarButton>
                            <ToolbarButton
                                label={t('dashboard_guide.toolbar_link', {
                                    defaultValue: 'Link',
                                })}
                                onClick={handleLink}
                            >
                                <Link2 />
                            </ToolbarButton>
                            <ToolbarButton
                                label={t('dashboard_guide.toolbar_image', {
                                    defaultValue: 'Image',
                                })}
                                onClick={handleImage}
                            >
                                <Image />
                            </ToolbarButton>
                            <ToolbarButton
                                label={t('dashboard_guide.toolbar_youtube', {
                                    defaultValue: 'YouTube',
                                })}
                                onClick={handleYouTube}
                            >
                                <Youtube />
                            </ToolbarButton>
                        </div>

                        <div
                            ref={editorRef}
                            contentEditable
                            suppressContentEditableWarning
                            className="min-h-[320px] rounded-md border bg-background p-4 text-sm leading-6 outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 [&_a]:text-primary [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:pl-4 [&_blockquote]:text-muted-foreground [&_figcaption]:text-sm [&_figcaption]:text-muted-foreground [&_figure]:space-y-2 [&_h2]:text-lg [&_h2]:font-semibold [&_h3]:text-base [&_h3]:font-semibold [&_h4]:font-medium [&_iframe]:aspect-video [&_iframe]:w-full [&_iframe]:rounded-md [&_iframe]:border [&_iframe]:bg-black [&_img]:max-h-[420px] [&_img]:w-full [&_img]:rounded-md [&_img]:border [&_img]:object-contain [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:leading-6 [&_ul]:list-disc [&_ul]:pl-5"
                            onInput={syncContent}
                            onPaste={handlePaste}
                            aria-label={t(
                                'dashboard_guide.editor_placeholder',
                                {
                                    defaultValue:
                                        'Write customer dashboard instructions here.',
                                },
                            )}
                        />
                        <InputError message={errors.content} />

                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing}>
                                <Save />
                                {processing
                                    ? t('common.saving', {
                                          defaultValue: 'Saving...',
                                      })
                                    : t('common.save', {
                                          defaultValue: 'Save',
                                      })}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>

            <div className="space-y-3">
                <h2 className="text-base font-medium">
                    {t('dashboard_guide.preview', {
                        defaultValue: 'Preview',
                    })}
                </h2>
                <DashboardGuideCard t={t} content={previewHtml} />
                {previewHtml === '' && (
                    <div className="rounded-md border border-dashed p-6 text-sm text-muted-foreground">
                        {t('dashboard_guide.empty_preview', {
                            defaultValue:
                                'Preview appears after adding instruction content.',
                        })}
                    </div>
                )}
            </div>
        </div>
    );
};

DashboardGuideConfig.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={[{ title: 'menu.dashboard_guide' }]}
        children={page}
    />
);

export default DashboardGuideConfig;
