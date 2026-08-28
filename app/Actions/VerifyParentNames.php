<?php

namespace App\Actions;

use Illuminate\Support\Str;

/**
 * Checks the answer to "Who is this baby list for?".
 *
 * The point of this gate is to keep out drive-by bots and passers-by who happen on the URL,
 * not to be a security boundary. So it is deliberately forgiving: case, accents, punctuation,
 * joining words and single-character typos are all tolerated. What it will not accept is a
 * name that is not one of the family's.
 */
class VerifyParentNames
{
    /**
     * Names that identify the family, in normalised form.
     *
     * @var list<string>
     */
    private const array ACCEPTED = ['emma', 'anders', 'learmonth'];

    /**
     * Words people naturally use to join two names, which carry no meaning here.
     *
     * @var list<string>
     */
    private const array JOINERS = ['and', 'plus', 'n'];

    /**
     * Determine whether the given answer identifies the family.
     */
    public function __invoke(?string $answer): bool
    {
        $words = $this->normalise($answer ?? '');

        if ($words === []) {
            return false;
        }

        foreach ($words as $word) {
            if (! $this->matchesAcceptedName($word)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reduce a free-text answer to a list of meaningful lowercase words.
     *
     * @return list<string>
     */
    private function normalise(string $answer): array
    {
        $answer = Str::lower(Str::ascii($answer));
        $answer = preg_replace('/[^a-z\s]+/', ' ', $answer) ?? '';

        $words = preg_split('/\s+/', trim($answer), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            $words,
            fn (string $word): bool => ! in_array($word, self::JOINERS, true),
        ));
    }

    /**
     * Determine whether a single word is one of the family's names, allowing a single typo
     * in words long enough that one accepted name cannot be mistyped into another.
     */
    private function matchesAcceptedName(string $word): bool
    {
        foreach (self::ACCEPTED as $name) {
            if ($word === $name) {
                return true;
            }

            if (Str::length($name) >= 4 && $this->isWithinOneEdit($word, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether two words are at most one edit apart, counting a swap of two neighbouring
     * letters as one edit.
     *
     * PHP's levenshtein() scores a swap as two edits, which would turn the single most
     * common way of mistyping a name -- "Andres" for "Anders" -- into a rejection.
     */
    private function isWithinOneEdit(string $word, string $name): bool
    {
        if (abs(strlen($word) - strlen($name)) > 1) {
            return false;
        }

        if (levenshtein($word, $name) <= 1) {
            return true;
        }

        // Only a swap of two adjacent letters is left to consider, which requires equal
        // lengths and exactly two differing positions, side by side and crossed over.
        if (strlen($word) !== strlen($name)) {
            return false;
        }

        $differences = [];

        for ($i = 0, $length = strlen($word); $i < $length; $i++) {
            if ($word[$i] !== $name[$i]) {
                $differences[] = $i;
            }

            if (count($differences) > 2) {
                return false;
            }
        }

        return count($differences) === 2
            && $differences[1] === $differences[0] + 1
            && $word[$differences[0]] === $name[$differences[1]]
            && $word[$differences[1]] === $name[$differences[0]];
    }
}
