/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

export abstract class AbstractInlineModule {
  protected setButtonState(button: HTMLButtonElement, interactable: boolean): void {
    button.disabled = !interactable;
  }

  abstract initialize(trigger: HTMLElement): void;
}
