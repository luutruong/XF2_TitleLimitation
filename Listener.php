<?php
/**
 * @license
 * Copyright 2018 TruongLuu. All Rights Reserved.
 */

namespace Truonglv\TitleLimitation;

use XF\Entity\User;
use XF\Entity\Thread;

class Listener
{
    /**
     * @param \XF\Mvc\Entity\Entity $entity
     * @return void
     */
    public static function onThreadPreSave(\XF\Mvc\Entity\Entity $entity)
    {
        if (!($entity instanceof Thread)) {
            return;
        }

        if (!$entity->isChanged('title')) {
            return;
        }

        $options = \XF::options();

        /** @var User|null $poster */
        $poster = $entity->User;
        if ($poster && $poster->isMemberOf($options->tl_TitleLimitation_excludeGroups)) {
            return;
        }

        $title = $entity->title;
        $titleStyle = (int) $options->tl_TitleLimitation_titleStyle;
        if ($titleStyle === 1) {
            $title = utf8_strtolower($title);
        } elseif ($titleStyle === 2) {
            $title = utf8_ucfirst($title);
        } elseif ($titleStyle === 3) {
            $title = utf8_ucwords($title);
        }

        if ($title !== $entity->title) {
            $entity->set('title', $title);
        }

        $titleLength = utf8_strlen($entity->title);
        $minLength = $options->tl_TitleLimitation_minLength;
        $maxLength = $options->tl_TitleLimitation_maxLength;

        if ($titleLength < $minLength) {
            $entity->error(\XF::phrase('tl_title_limitation_please_enter_title_is_at_least_x_characters', [
                'count' => $minLength
            ]), 'title');
        } elseif ($titleLength > $options->tl_TitleLimitation_maxLength) {
            $entity->error(\XF::phrase('tl_title_limitation_please_enter_title_is_at_most_x_characters', [
                'count' => $maxLength
            ]), 'title');
        }

        $disallowChars = $options->tl_TitleLimitation_disallowChars;
        if (!empty($disallowChars)) {
            $disallowChars = preg_split("/(\n|\r\n)/", $disallowChars, -1, PREG_SPLIT_NO_EMPTY);
            if (empty($disallowChars)) {
                return;
            }

            $disallowChars = array_map('trim', $disallowChars);
            $disallowChars = array_map('preg_quote', $disallowChars);
            $disallowChars = implode($disallowChars, '|');
            $disallowChars = str_replace('#', '\#', $disallowChars);

            if (preg_match('#(' . $disallowChars . ')#i', $entity->title)) {
                $entity->error(\XF::phrase('tl_title_limitation_title_must_not_contains_disallow_characters'), 'title');
            }
        }
    }
}
