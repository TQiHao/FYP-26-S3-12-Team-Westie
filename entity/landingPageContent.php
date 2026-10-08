<?php

class LandingPageContent
{
    private $id;
    private $sectionKey;
    private $title;
    private $subtitle;
    private $content;
    private $imagePath;
    private $updatedAt;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->sectionKey = $data['sectionKey'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->subtitle = $data['subtitle'] ?? null;
        $this->content = $data['content'] ?? null;
        $this->imagePath = $data['imagePath'] ?? null;
        $this->updatedAt = $data['updatedAt'] ?? null;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getSectionKey()
    {
        return $this->sectionKey;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function getSubtitle()
    {
        return $this->subtitle;
    }
    public function getContent()
    {
        return $this->content;
    }
    public function getImagePath()
    {
        return $this->imagePath;
    }
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getFeatureBoxes()
    {
        if (empty($this->content)) {
            return [];
        }
        $decoded = json_decode($this->content, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'sectionKey' => $this->sectionKey,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'content' => $this->content,
            'imagePath' => $this->imagePath,
            'updatedAt' => $this->updatedAt,
        ];
    }
}