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
var __decorate = function (e, t, o, i) {
  var n, r = arguments.length, s = r < 3 ? t : null === i ? i = Object.getOwnPropertyDescriptor(t, o) : i;
  if ("object" == typeof Reflect && "function" == typeof Reflect.decorate) s = Reflect.decorate(e, t, o, i); else for (var a = e.length - 1; a >= 0; a--) (n = e[a]) && (s = (r < 3 ? n(s) : r > 3 ? n(t, o, s) : n(t, o)) || s);
  return r > 3 && s && Object.defineProperty(t, o, s), s
};
import {html, LitElement, nothing} from "lit";
import {customElement, property, query} from "lit/decorators.js";
import {until} from "lit/directives/until.js";
import {lll} from "@typo3/core/lit-helper.js";
import {PageTree} from "@typo3/backend/page-tree/page-tree.js";
import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Persistent from "@typo3/backend/storage/persistent.js";
import {ModuleUtility} from "@typo3/backend/module.js";
import ContextMenu from "@typo3/backend/context-menu.js";
import *as d3selection from "d3-selection";
import {KeyTypesEnum as KeyTypes} from "@typo3/backend/enum/key-types.js";
import {Toolbar} from "@typo3/backend/svg-tree.js";
import {DragDrop, DraggablePositionEnum} from "@typo3/backend/tree/drag-drop.js";
import Modal from "@typo3/backend/modal.js";
import Severity from "@typo3/backend/severity.js";
import {ModuleStateStorage} from "@typo3/backend/storage/module-state-storage.js";

export const navigationComponentName = "typo3-backend-navigation-component-pagetree";
let EditablePageTree = class extends PageTree {
  selectFirstNode() {
    this.selectNode(this.nodes[0], !0), this.focusNode(this.nodes[0])
  }

  sendChangeCommand(e) {
    let t = "", o = 0;
    if (e.target && (o = e.target.identifier, "after" === e.position && (o = -o)), "new" === e.command) t = "&data[pages][NEW_1][pid]=" + o + "&data[pages][NEW_1][title]=" + encodeURIComponent(e.name) + "&data[pages][NEW_1][doktype]=" + e.type; else if ("edit" === e.command) t = "&data[pages][" + e.uid + "][" + e.nameSourceField + "]=" + encodeURIComponent(e.title); else if ("delete" === e.command) {
      const o = ModuleStateStorage.current("web");
      e.uid === o.identifier && this.selectFirstNode(), t = "&cmd[pages][" + e.uid + "][delete]=1"
    } else t = "cmd[pages][" + e.uid + "][" + e.command + "]=" + o;
    this.requestTreeUpdate(t).then((e => {
      e && e.hasErrors ? (this.errorNotification(e.messages, !1), this.nodesContainer.selectAll(".node").remove(), this.updateVisibleNodes(), this.nodesRemovePlaceholder()) : this.refreshOrFilterTree()
    }))
  }

  focusNode(e) {
    this.nodeIsEdit || super.focusNode(e)
  }

  nodesUpdate(e) {
    return super.nodesUpdate.call(this, e).call(this.initializeDragForNode())
  }

  updateNodeBgClass(e) {
    return super.updateNodeBgClass.call(this, e).call(this.initializeDragForNode())
  }

  initializeDragForNode() {
    return this.dragDrop.connectDragHandler(new PageTreeNodeDragHandler(this, this.dragDrop))
  }

  removeEditedText() {
    const e = d3selection.selectAll(".node-edit");
    if (e.size()) try {
      e.remove(), this.nodeIsEdit = !1
    } catch (e) {
    }
  }

  appendTextElement(e) {
    let t = 0;
    return super.appendTextElement(e).on("click", ((e, o) => {
      if ("0" === o.identifier) return this.selectNode(o, !0), void this.focusNode(o);
      1 == ++t && setTimeout((() => {
        1 === t ? (this.selectNode(o, !0), this.focusNode(o)) : this.editNodeLabel(o), t = 0
      }), 300)
    }))
  }

  sendEditNodeLabelCommand(e) {
    const t = "&data[pages][" + e.identifier + "][" + e.nameSourceField + "]=" + encodeURIComponent(e.newName);
    this.requestTreeUpdate(t, e).then((t => {
      t && t.hasErrors ? this.errorNotification(t.messages, !1) : e.name = e.newName, this.refreshOrFilterTree()
    }))
  }

  requestTreeUpdate(e, t = null) {
    return this.nodesAddPlaceholder(t), new AjaxRequest(top.TYPO3.settings.ajaxUrls.record_process).post(e, {
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then((e => e.resolve())).catch((e => {
      this.errorNotification(e, !0)
    }))
  }

  editNodeLabel(e) {
    e.allowEdit && (this.disableFocusedNodes(), e.focused = !0, this.updateVisibleNodes(), this.removeEditedText(), this.nodeIsEdit = !0, d3selection.select(this.svg.node().parentNode).append("input").attr("class", "node-edit").style("top", e.y + this.settings.marginTop + "px").style("left", e.x + this.textPosition + 5 + "px").style("width", "calc(100% - " + (e.x + this.textPosition + 5) + "px)").style("height", this.settings.nodeHeight + "px").attr("type", "text").attr("value", e.name).on("keydown", (t => {
      const o = t.keyCode;
      if (o === KeyTypes.ENTER || o === KeyTypes.TAB) {
        const o = t.target.value.trim();
        this.nodeIsEdit = !1, this.removeEditedText(), o.length && o !== e.name && (e.nameSourceField = e.nameSourceField || "title", e.newName = o, this.sendEditNodeLabelCommand(e))
      } else o === KeyTypes.ESCAPE && (this.nodeIsEdit = !1, this.removeEditedText());
      this.focusNode(e)
    })).on("blur", (t => {
      if (!this.nodeIsEdit) return;
      const o = t.target.value.trim();
      o.length && o !== e.name && (e.nameSourceField = e.nameSourceField || "title", e.newName = o, this.sendEditNodeLabelCommand(e)), this.removeEditedText(), this.focusNode(e)
    })).node().select())
  }
};
EditablePageTree = __decorate([customElement("typo3-backend-navigation-component-pagetree-tree")], EditablePageTree);
export {EditablePageTree};
let PageTreeNavigationComponent = class extends LitElement {
  constructor() {
    super(...arguments), this.mountPointPath = null, this.configuration = null, this.refresh = () => {
      this.tree.refreshOrFilterTree()
    }, this.setMountPoint = e => {
      this.setTemporaryMountPoint(e.detail.pageId)
    }, this.selectFirstNode = () => {
      this.tree.selectFirstNode()
    }, this.toggleExpandState = e => {
      const t = e.detail.node;
      t && Persistent.set("BackendComponents.States.Pagetree.stateHash." + t.stateIdentifier, t.expanded ? "1" : "0")
    }, this.loadContent = e => {
      const t = e.detail.node;
      if (!t?.checked) return;
      if (ModuleStateStorage.update("web", t.identifier, !0, t.stateIdentifier.split("_")[0]), !1 === e.detail.propagate) return;
      const o = top.TYPO3.ModuleMenu.App;
      let i = ModuleUtility.getFromName(o.getCurrentModule()).link;
      i += i.includes("?") ? "&" : "?", top.TYPO3.Backend.ContentContainer.setUrl(i + "id=" + t.identifier)
    }, this.showContextMenu = e => {
      const t = e.detail.node;
      t && ContextMenu.show(t.itemType, parseInt(t.identifier, 10), "tree", "", "", this.tree.getElementFromNode(t))
    }, this.selectActiveNode = e => {
      const t = ModuleStateStorage.current("web").selection, o = e.detail.nodes;
      e.detail.nodes = o.map((e => (e.stateIdentifier === t && (e.checked = !0), e)))
    }
  }

  connectedCallback() {
    super.connectedCallback(), document.addEventListener("typo3:pagetree:refresh", this.refresh), document.addEventListener("typo3:pagetree:mountPoint", this.setMountPoint), document.addEventListener("typo3:pagetree:selectFirstNode", this.selectFirstNode)
  }

  disconnectedCallback() {
    document.removeEventListener("typo3:pagetree:refresh", this.refresh), document.removeEventListener("typo3:pagetree:mountPoint", this.setMountPoint), document.removeEventListener("typo3:pagetree:selectFirstNode", this.selectFirstNode), super.disconnectedCallback()
  }

  createRenderRoot() {
    return this
  }

  render() {
    return html`
      <div id="typo3-pagetree" class="svg-tree">
        ${until(this.renderTree(),this.renderLoader())}
      </div>
    `}getConfiguration(){if(null!==this.configuration)return Promise.resolve(this.configuration);const e=top.TYPO3.settings.ajaxUrls.page_tree_configuration;return new AjaxRequest(e).get().then((async e=>{const t=await e.resolve("json");return this.configuration=t,this.mountPointPath=t.temporaryMountPoint||null,t}))}renderTree(){return this.getConfiguration().then((e=>html`
          <div>
            <typo3-backend-navigation-component-pagetree-toolbar id="typo3-pagetree-toolbar" class="svg-toolbar" .tree="${this.tree}"></typo3-backend-navigation-component-pagetree-toolbar>
            <div id="typo3-pagetree-treeContainer" class="navigation-tree-container">
              ${this.renderMountPoint()}
              <typo3-backend-navigation-component-pagetree-tree id="typo3-pagetree-tree" class="svg-tree-wrapper" .setup=${e} @svg-tree:initialized=${()=>{this.tree.dragDrop=new PageTreeDragDrop(this.tree),this.toolbar.tree=this.tree,this.tree.addEventListener("typo3:svg-tree:expand-toggle",this.toggleExpandState),this.tree.addEventListener("typo3:svg-tree:node-selected",this.loadContent),this.tree.addEventListener("typo3:svg-tree:node-context",this.showContextMenu),this.tree.addEventListener("typo3:svg-tree:nodes-prepared",this.selectActiveNode)}}></typo3-backend-navigation-component-pagetree-tree>
            </div>
          </div>
          ${this.renderLoader()}
        `))}renderLoader(){return html`
      <div class="svg-tree-loader">
        <typo3-backend-icon identifier="spinner-circle" size="large"></typo3-backend-icon>
      </div>
    `}unsetTemporaryMountPoint(){this.mountPointPath=null,Persistent.unset("pageTree_temporaryMountPoint").then((()=>{this.tree.refreshTree()}))}renderMountPoint(){return null===this.mountPointPath?nothing:html`
      <div class="node-mount-point">
        <div class="node-mount-point__icon"><typo3-backend-icon identifier="actions-info-circle" size="small"></typo3-backend-icon></div>
        <div class="node-mount-point__text">${this.mountPointPath}</div>
        <div class="node-mount-point__icon mountpoint-close" @click="${()=>this.unsetTemporaryMountPoint()}" title="${lll("labels.temporaryDBmount")}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </div>
      </div>
`
}

  setTemporaryMountPoint(e) {
    new AjaxRequest(this.configuration.setTemporaryMountPointUrl).post("pid=" + e, {
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then((e => e.resolve())).then((e => {
      e && e.hasErrors ? (this.tree.errorNotification(e.message, !0), this.tree.updateVisibleNodes()) : (this.mountPointPath = e.mountPointPath, this.tree.refreshOrFilterTree())
    })).catch((e => {
      this.tree.errorNotification(e, !0)
    }))
  }
};
__decorate([property({type: String})], PageTreeNavigationComponent.prototype, "mountPointPath", void 0), __decorate([query(".svg-tree-wrapper")], PageTreeNavigationComponent.prototype, "tree", void 0), __decorate([query("typo3-backend-navigation-component-pagetree-toolbar")], PageTreeNavigationComponent.prototype, "toolbar", void 0), PageTreeNavigationComponent = __decorate([customElement("typo3-backend-navigation-component-pagetree")], PageTreeNavigationComponent);
export {PageTreeNavigationComponent};
let PageTreeToolbar = class extends Toolbar {
  constructor() {
    super(...arguments), this.tree = null
  }

  initializeDragDrop(e) {
    this.tree?.settings?.doktypes?.length && this.tree.settings.doktypes.forEach((t => {
      if (t.icon) {
        const o = this.querySelector('[data-tree-icon="' + t.icon + '"]');
        d3selection.select(o).call(this.dragToolbar(t, e))
      } else console.warn("Missing icon definition for doktype: " + t.nodeType)
    }))
  }

  updated(e) {
    e.forEach(((e, t) => {
      "tree" === t && null !== this.tree && this.initializeDragDrop(this.tree.dragDrop)
    }))
  }

  getSelectedLanguage() {
    return this.querySelector("#svgToolbarLanguage").value;
  }

  getSelectedSourceLanguage() {
    return this.querySelector("#svgToolbarSourceLanguage").value;
  }

  render() {
    return html`
      <div class="tree-toolbar">
        <div class="svg-toolbar__menu">
          <div class="svg-toolbar__search">
              <label for="svgToolbarSearch" class="visually-hidden">
                ${lll("labels.label.searchString")}
              </label>
              <input type="search" id="svgToolbarSearch" class="form-control form-control-sm search-input" placeholder="${lll("tree.searchTermInfo")}">
          </div>
        </div>
        <div class="svg-toolbar__submenu">
          <div class="svg-toolbar__language">
            <label for="svgToolbarLanguage" class="visually-hidden">
              ${lll("labels.label.searchString")}
            </label>
            <select id="svgToolbarLanguage" class="form-select form-select-sm">
              <option value="de">Deutsch</option>
            </select>
          </div>
        </div>
        <div class="svg-toolbar__submenu">
          <div class="svg-toolbar__source-language">
            <label for="svgToolbarSourceLanguage" class="">
              Übersetzen von
            </label>
            <select id="svgToolbarSourceLanguage" class="form-select form-select-sm">
              <option>-----</option>
              <option value="en">English</option>
              <option value="nl">Dutch</option>
            </select>
          </div>
        </div>
        <div class="svg-toolbar__submenu">
          ${this.tree?.settings?.doktypes?.length?this.tree.settings.doktypes.map((e=>html`
                <div class="svg-toolbar__menuitem svg-toolbar__drag-node" data-tree-icon="${e.icon}" data-node-type="${e.nodeType}"
                     title="${e.title}">
                  <typo3-backend-icon identifier="${e.icon}" size="small"></typo3-backend-icon>
                </div>
              `)):""}
          <a class="svg-toolbar__menuitem nav-link dropdown-toggle dropdown-toggle-no-chevron float-end" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"><typo3-backend-icon identifier="actions-menu-alternative" size="small"></typo3-backend-icon></a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <button class="dropdown-item" @click="${()=>this.refreshTree()}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll("labels.refresh")}
                  </span>
                </span>
              </button>
            </li>
            <li>
              <button class="dropdown-item" @click="${e=>this.collapseAll(e)}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="apps-pagetree-category-collapse-all" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll("labels.collapse")}
                  </span>
                </span>
              </button>
            </li>
          </ul>
        </div>
      </div>
    `
  }

  dragToolbar(e, t) {
    return t.connectDragHandler(new ToolbarDragHandler(e, this.tree, t))
  }
};
__decorate([property({type: EditablePageTree})], PageTreeToolbar.prototype, "tree", void 0), PageTreeToolbar = __decorate([customElement("typo3-backend-navigation-component-pagetree-toolbar")], PageTreeToolbar);

class PageTreeDragDrop extends DragDrop {
  getDropCommandDetails(e, t = "", o = null) {
    const i = this.tree.nodes, n = o.identifier;
    let r = this.tree.settings.nodeDragPosition, s = e || o;
    if (n === s.identifier && "delete" !== t) return null;
    if (r === DraggablePositionEnum.BEFORE) {
      const t = i.indexOf(e), o = this.setNodePositionAndTarget(t);
      if (null === o) return null;
      r = o.position, s = o.target
    }
    return {node: o, uid: n, target: s, position: r, command: t}
  }

  updateStateOfHoveredNode(e) {
    const t = this.tree.svg.select(".node-over");
    if (t.size() && this.tree.isOverSvg) {
      this.createPositioningLine();
      const o = d3selection.pointer(e, t.node())[1];
      o < 3 ? (this.updatePositioningLine(this.tree.hoveredNode), 0 === this.tree.hoveredNode.depth ? this.addNodeDdClass("nodrop") : this.tree.hoveredNode.firstChild ? this.addNodeDdClass("ok-above") : this.addNodeDdClass("ok-between"), this.tree.settings.nodeDragPosition = DraggablePositionEnum.BEFORE) : o > 17 ? (this.hidePositioningLine(), this.tree.hoveredNode.expanded && this.tree.hoveredNode.hasChildren ? (this.addNodeDdClass("ok-append"), this.tree.settings.nodeDragPosition = DraggablePositionEnum.INSIDE) : (this.updatePositioningLine(this.tree.hoveredNode), this.tree.hoveredNode.lastChild ? this.addNodeDdClass("ok-below") : this.addNodeDdClass("ok-between"), this.tree.settings.nodeDragPosition = DraggablePositionEnum.AFTER)) : (this.hidePositioningLine(), this.addNodeDdClass("ok-append"), this.tree.settings.nodeDragPosition = DraggablePositionEnum.INSIDE)
    } else this.hidePositioningLine(), this.addNodeDdClass("nodrop")
  }

  setNodePositionAndTarget(e) {
    const t = this.tree.nodes, o = t[e].depth;
    e > 0 && e--;
    const i = t[e].depth, n = this.tree.nodes[e];
    if (i === o) return {position: DraggablePositionEnum.AFTER, target: n};
    if (i < o) return {position: DraggablePositionEnum.INSIDE, target: n};
    for (let i = e; i >= 0; i--) {
      if (t[i].depth === o) return {position: DraggablePositionEnum.AFTER, target: this.tree.nodes[i]};
      if (t[i].depth < o) return {position: DraggablePositionEnum.AFTER, target: t[i]}
    }
    return null
  }

  isDropAllowed(e, t) {
    return !!this.tree.settings.allowDragMove && (!!this.tree.isOverSvg && (!!this.tree.hoveredNode && (!t.isOver && !this.isTheSameNode(e, t))))
  }
}

class ToolbarDragHandler {
  constructor(e, t, o) {
    this.dragStarted = !1, this.startPageX = 0, this.startPageY = 0, this.id = "", this.name = "", this.icon = "", this.id = e.nodeType, this.name = e.title, this.icon = e.icon, this.tree = t, this.dragDrop = o
  }

  onDragStart(e) {
    return this.dragStarted = !1, this.startPageX = e.pageX, this.startPageY = e.pageY, !0
  }

  onDragOver(e) {
    return !!this.dragDrop.isDragNodeDistanceMore(e, this) && (this.dragStarted = !0, this.dragDrop.getDraggable() || this.dragDrop.createDraggable("#icon-" + this.icon, this.name), this.dragDrop.openNodeTimeout(), this.dragDrop.updateDraggablePosition(e), this.dragDrop.updateStateOfHoveredNode(e), !0)
  }

  onDrop(e, t) {
    return !!this.dragStarted && (this.dragDrop.cleanupDrop(), !!this.dragDrop.isDropAllowed(this.tree.hoveredNode, t) && (this.addNewNode({
      type: this.id,
      name: this.name,
      icon: this.icon,
      position: this.tree.settings.nodeDragPosition,
      target: this.tree.hoveredNode
    }), !0))
  }

  addNewNode(e) {
    const t = e.target;
    let o = this.tree.nodes.indexOf(t);
    const i = {};
    if (this.tree.disableFocusedNodes(), i.focused = !0, this.tree.updateVisibleNodes(), i.command = "new", i.type = e.type, i.identifier = "-1", i.target = t, i.parents = t.parents, i.parentsStateIdentifier = t.parentsStateIdentifier, i.depth = t.depth, i.position = e.position, i.name = void 0 !== e.title ? e.title : TYPO3.lang["tree.defaultPageTitle"], i.y = i.y || i.target.y, i.x = i.x || i.target.x, this.tree.nodeIsEdit = !0, e.position === DraggablePositionEnum.INSIDE && (i.depth++, i.parents.unshift(o), i.parentsStateIdentifier.unshift(this.tree.nodes[o].stateIdentifier), this.tree.nodes[o].hasChildren = !0, this.tree.showChildren(this.tree.nodes[o])), e.position !== DraggablePositionEnum.INSIDE && e.position !== DraggablePositionEnum.AFTER || o++, e.icon && (i.icon = e.icon), i.position === DraggablePositionEnum.BEFORE) {
      const e = this.dragDrop.setNodePositionAndTarget(o);
      null !== e && (i.position = e.position, i.target = e.target)
    }
    this.tree.nodes.splice(o, 0, i), this.tree.setParametersNode(), this.tree.prepareDataForVisibleNodes(), this.tree.updateVisibleNodes(), this.tree.removeEditedText(), d3selection.select(this.tree.svg.node().parentNode).append("input").attr("class", "node-edit").style("top", i.y + this.tree.settings.marginTop + "px").style("left", i.x + this.tree.textPosition + 5 + "px").style("width", "calc(100% - " + (i.x + this.tree.textPosition + 5) + "px)").style("height", this.tree.settings.nodeHeight + "px").attr("text", "text").attr("value", i.name).on("keydown", (e => {
      const t = e.target, o = e.keyCode;
      if (13 === o || 9 === o) {
        this.tree.nodeIsEdit = !1;
        const e = t.value.trim();
        e.length ? (i.name = e, this.tree.removeEditedText(), this.tree.sendChangeCommand(i)) : this.removeNode(i)
      } else 27 === o && (this.tree.nodeIsEdit = !1, this.removeNode(i))
    })).on("blur", (e => {
      if (this.tree.nodeIsEdit && this.tree.nodes.indexOf(i) > -1) {
        const t = e.target.value.trim();
        t.length ? (i.name = t, this.tree.removeEditedText(), this.tree.sendChangeCommand(i)) : this.removeNode(i)
      }
    })).node().select()
  }

  removeNode(e) {
    const t = this.tree.nodes.indexOf(e);
    this.tree.nodes[t - 1].depth == e.depth || this.tree.nodes[t + 1] && this.tree.nodes[t + 1].depth == e.depth || (this.tree.nodes[t - 1].hasChildren = !1), this.tree.nodes.splice(t, 1), this.tree.setParametersNode(), this.tree.prepareDataForVisibleNodes(), this.tree.updateVisibleNodes(), this.tree.removeEditedText()
  }
}

class PageTreeNodeDragHandler {
  constructor(e, t) {
    this.dragStarted = !1, this.startPageX = 0, this.startPageY = 0, this.nodeIsOverDelete = !1, this.tree = e, this.dragDrop = t
  }

  onDragStart(e, t) {
    return !0 === this.tree.settings.allowDragMove && 0 !== t.depth && (this.dropZoneDelete = null, t.allowDelete && (this.dropZoneDelete = this.tree.nodesContainer.select('.node[data-state-id="' + t.stateIdentifier + '"]').append("g").attr("class", "nodes-drop-zone").attr("height", this.tree.settings.nodeHeight), this.nodeIsOverDelete = !1, this.dropZoneDelete.append("rect").attr("height", this.tree.settings.nodeHeight).attr("width", "50px").attr("x", 0).attr("y", 0).on("mouseover", (() => {
      this.nodeIsOverDelete = !0
    })).on("mouseout", (() => {
      this.nodeIsOverDelete = !1
    })), this.dropZoneDelete.append("text").text(TYPO3.lang.deleteItem).attr("x", 5).attr("y", this.tree.settings.nodeHeight / 2 + 4), this.dropZoneDelete.node().dataset.open = "false", this.dropZoneDelete.node().style.transform = this.getDropZoneCloseTransform(t)), this.startPageX = e.pageX, this.startPageY = e.pageY, this.dragStarted = !1, !0)
  }

  onDragOver(e, t) {
    return !!this.dragDrop.isDragNodeDistanceMore(e, this) && (this.dragStarted = !0, !0 === this.tree.settings.allowDragMove && 0 !== t.depth && (this.dragDrop.getDraggable() || this.dragDrop.createDraggableFromExistingNode(t), this.tree.settings.nodeDragPosition = !1, this.dragDrop.openNodeTimeout(), this.dragDrop.updateDraggablePosition(e), this.dragDrop.isDropAllowed(this.tree.hoveredNode, t) ? this.tree.hoveredNode ? this.dropZoneDelete && "false" !== this.dropZoneDelete.node().dataset.open ? this.animateDropZone("hide", this.dropZoneDelete.node(), t) : this.dragDrop.updateStateOfHoveredNode(e) : (this.dragDrop.addNodeDdClass("nodrop"), this.dragDrop.hidePositioningLine()) : (this.dragDrop.addNodeDdClass("nodrop"), this.tree.isOverSvg || this.dragDrop.hidePositioningLine(), this.dropZoneDelete && "true" !== this.dropZoneDelete.node().dataset.open && this.tree.isOverSvg && this.animateDropZone("show", this.dropZoneDelete.node(), t)), !0))
  }

  onDrop(e, t) {
    if (this.dropZoneDelete && "true" === this.dropZoneDelete.node().dataset.open) {
      const e = this.dropZoneDelete;
      this.animateDropZone("hide", this.dropZoneDelete.node(), t, (() => {
        e.remove(), this.dropZoneDelete = null
      }))
    } else this.dropZoneDelete && "false" === this.dropZoneDelete.node().dataset.open ? (this.dropZoneDelete.remove(), this.dropZoneDelete = null) : this.dropZoneDelete = null;
    if (!this.dragStarted || !0 !== this.tree.settings.allowDragMove || 0 === t.depth) return !1;
    if (this.dragDrop.cleanupDrop(), this.dragDrop.isDropAllowed(this.tree.hoveredNode, t)) {
      const e = this.dragDrop.getDropCommandDetails(this.tree.hoveredNode, "", t);
      if (null === e) return !1;
      let o = e.position === DraggablePositionEnum.INSIDE ? TYPO3.lang["mess.move_into"] : TYPO3.lang["mess.move_after"];
      o = o.replace("%s", e.node.name).replace("%s", e.target.name);
      const i = Modal.confirm(TYPO3.lang.move_page, o, Severity.warning, [{
        text: TYPO3.lang["labels.cancel"] || "Cancel",
        active: !0,
        btnClass: "btn-default",
        name: "cancel"
      }, {
        text: TYPO3.lang["cm.copy"] || "Copy",
        btnClass: "btn-warning",
        name: "copy"
      }, {text: TYPO3.lang["labels.move"] || "Move", btnClass: "btn-warning", name: "move"}]);
      i.addEventListener("button.clicked", (t => {
        const o = t.target;
        "move" === o.name ? (e.command = "move", this.tree.sendChangeCommand(e)) : "copy" === o.name && (e.command = "copy", this.tree.sendChangeCommand(e)), i.hideModal()
      }))
    } else if (this.nodeIsOverDelete) {
      const e = this.dragDrop.getDropCommandDetails(this.tree.hoveredNode, "delete", t);
      if (null === e) return !1;
      if (this.tree.settings.displayDeleteConfirmation) {
        Modal.confirm(TYPO3.lang["mess.delete.title"], TYPO3.lang["mess.delete"].replace("%s", e.node.name), Severity.warning, [{
          text: TYPO3.lang["labels.cancel"] || "Cancel",
          active: !0,
          btnClass: "btn-default",
          name: "cancel"
        }, {
          text: TYPO3.lang.delete || "Delete",
          btnClass: "btn-warning",
          name: "delete"
        }]).addEventListener("button.clicked", (t => {
          "delete" === t.target.name && this.tree.sendChangeCommand(e), Modal.dismiss()
        }))
      } else this.tree.sendChangeCommand(e)
    }
    return !0
  }

  getDropZoneOpenTransform(e) {
    return "translate(" + ((parseFloat(this.tree.svg.style("width")) || 300) - 58 - e.x) + "px, " + this.tree.settings.nodeHeight / 2 * -1 + "px)"
  }

  getDropZoneCloseTransform(e) {
    return "translate(" + ((parseFloat(this.tree.svg.style("width")) || 300) - e.x) + "px, " + this.tree.settings.nodeHeight / 2 * -1 + "px)"
  }

  animateDropZone(e, t, o, i = null) {
    t.classList.add("animating"), t.dataset.open = "show" === e ? "true" : "false";
    let n = [{transform: this.getDropZoneCloseTransform(o)}, {transform: this.getDropZoneOpenTransform(o)}];
    "show" !== e && (n = n.reverse());
    const r = function () {
      t.style.transform = n[1].transform, t.classList.remove("animating"), i && i()
    };
    "animate" in t ? t.animate(n, {duration: 300, easing: "cubic-bezier(.02, .01, .47, 1)"}).onfinish = r : r()
  }
}
