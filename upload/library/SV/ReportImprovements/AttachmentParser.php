<?php

class SV_ReportImprovements_AttachmentParser extends XenForo_BbCode_Parser
{
    protected $parser      = null;
    protected $report      = null;
    protected $contentInfo = null;

    public function __construct(XenForo_BbCode_Parser $parser, &$report, &$contentInfo)
    {
        $this->parser = $parser;
        $this->report = &$report;
        $this->contentInfo = &$contentInfo;
    }

    public function parse($text)
    {
        return $this->parser->parse($text);
    }

    public function render($text, array $extraStates = array())
    {
        $extraStates['viewAttachments'] = true;
        $extraStates['attachments'] = $this->contentInfo['attachments'];

        return $this->parser->render($text, $extraStates);
    }
}