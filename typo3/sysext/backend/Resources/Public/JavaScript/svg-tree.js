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
var __decorate = function (e, t, s, i) {
  var o, n = arguments.length, r = n < 3 ? t : null === i ? i = Object.getOwnPropertyDescriptor(t, s) : i;
  if ("object" == typeof Reflect && "function" == typeof Reflect.decorate) r = Reflect.decorate(e, t, s, i); else for (var a = e.length - 1; a >= 0; a--) (o = e[a]) && (r = (n < 3 ? o(r) : n > 3 ? o(t, s, r) : o(t, s)) || r);
  return n > 3 && r && Object.defineProperty(t, s, r), r
};
import {html, LitElement} from "lit";
import {customElement, property, state} from "lit/decorators.js";
import *as d3selection from "d3-selection";
import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Notification from "@typo3/backend/notification.js";
import {KeyTypesEnum as KeyTypes} from "@typo3/backend/enum/key-types.js";
import Icons from "@typo3/backend/icons.js";
import {MarkupIdentifiers} from "@typo3/backend/enum/icon-types.js";
import {lll} from "@typo3/core/lit-helper.js";
import DebounceEvent from "@typo3/core/event/debounce-event.js";
import "@typo3/backend/element/icon-element.js";

export class SvgTree extends LitElement {
  constructor() {
    super(...arguments), this.setup = null, this.settings = {
      showIcons: !1,
      marginTop: 15,
      nodeHeight: 26,
      icon: {size: 16, containerSize: 20},
      indentWidth: 20,
      width: 300,
      duration: 400,
      dataUrl: "",
      filterUrl: "",
      defaultProperties: {},
      expandUpToLevel: null,
      actions: []
    }, this.isOverSvg = !1, this.svg = null, this.container = null, this.nodesContainer = null, this.nodesBgContainer = null, this.hoveredNode = null, this.nodes = [], this.textPosition = 10, this.icons = {}, this.nodesActionsContainer = null, this.iconsContainer = null, this.linksContainer = null, this.data = new class {
      constructor() {
        this.links = [], this.nodes = []
      }
    }, this.viewportHeight = 0, this.scrollBottom = 0, this.searchTerm = null, this.unfilteredNodes = "", this.muteErrorNotifications = !1, this.networkErrorTitle = top.TYPO3.lang.tree_networkError, this.networkErrorMessage = top.TYPO3.lang.tree_networkErrorDescription, this.windowResized = () => {
      this.getClientRects().length > 0 && this.updateView()
    }
  }

  doSetup(e) {
    Object.assign(this.settings, e), this.settings.showIcons && (this.textPosition += this.settings.icon.containerSize), this.svg = d3selection.select(this).select("svg"), this.container = this.svg.select(".nodes-wrapper"), this.nodesBgContainer = this.container.select(".nodes-bg"), this.nodesActionsContainer = this.container.select(".nodes-actions"), this.linksContainer = this.container.select(".links"), this.nodesContainer = this.container.select(".nodes"), this.iconsContainer = this.svg.select("defs"), this.registerUnloadHandler(), this.updateScrollPosition(), this.loadCommonIcons(), this.loadData(), this.dispatchEvent(new Event("svg-tree:initialized"))
  }

  loadCommonIcons() {
    this.fetchIcon("actions-chevron-right", !1), this.fetchIcon("overlay-backenduser", !1), this.fetchIcon("actions-caret-right", !1), this.fetchIcon("actions-link", !1)
  }

  focusElement(e) {
    if (null === e) return;
    e.parentNode.querySelectorAll("[tabindex]").forEach((e => {
      e.setAttribute("tabindex", "-1")
    })), e.setAttribute("tabindex", "0"), e.focus()
  }

  focusNode(e) {
    this.disableFocusedNodes(), e.focused = !0, this.focusElement(this.getElementFromNode(e))
  }

  getNodeFromElement(e) {
    return null !== e && "stateId" in e.dataset ? this.getNodeByIdentifier(e.dataset.stateId) : null
  }

  getElementFromNode(e) {
    return this.querySelector("#identifier-" + this.getNodeStateIdentifier(e))
  }

  loadData() {
    this.nodesAddPlaceholder(),
      new AjaxRequest(this.settings.dataUrl+'&currentLanguage=de&sourceLanguage=en').get({cache: "no-cache"}).then((e => e.resolve())).then((e => {
      const t = Array.isArray(e) ? e : [];
      this.replaceData(t), this.nodesRemovePlaceholder(), this.updateScrollPosition(), this.updateVisibleNodes()
    })).catch((e => {
      throw this.errorNotification(e, !1), this.nodesRemovePlaceholder(), e
    }))
  }

  replaceData(e) {
    this.setParametersNode(e), this.prepareDataForVisibleNodes(), this.nodesContainer.selectAll(".node").remove(), this.nodesBgContainer.selectAll(".node-bg").remove(), this.nodesActionsContainer.selectAll(".node-action").remove(), this.linksContainer.selectAll(".link").remove(), this.updateVisibleNodes()
  }

  setParametersNode(e = null) {
    1 === (e = (e = e || this.nodes).map(((t, s) => {
      if (void 0 === t.command && (t = Object.assign({}, this.settings.defaultProperties, t)), t.expanded = null !== this.settings.expandUpToLevel ? t.depth < this.settings.expandUpToLevel : Boolean(t.expanded), t.parents = [], t.parentsStateIdentifier = [], t.depth > 0) {
        let i = t.depth;
        for (let o = s; o >= 0; o--) {
          const s = e[o];
          s.depth < i && (t.parents.push(o), t.parentsStateIdentifier.push(e[o].stateIdentifier), i = s.depth)
        }
      }
      return void 0 === t.checked && (t.checked = !1), void 0 === t.focused && (t.focused = !1), t
    }))).filter((e => 0 === e.depth)).length && (e[0].expanded = !0);
    const t = new CustomEvent("typo3:svg-tree:nodes-prepared", {detail: {nodes: e}, bubbles: !1});
    this.dispatchEvent(t), this.nodes = t.detail.nodes
  }

  nodesRemovePlaceholder() {
    const e = this.querySelector(".node-loader");
    e && (e.style.display = "none");
    const t = this.closest(".svg-tree"), s = t?.querySelector(".svg-tree-loader");
    s && (s.style.display = "none")
  }

  nodesAddPlaceholder(e = null) {
    if (e) {
      const t = this.querySelector(".node-loader");
      t && (t.style.top = "" + (e.y + this.settings.marginTop), t.style.display = "block")
    } else {
      const e = this.closest(".svg-tree"), t = e?.querySelector(".svg-tree-loader");
      t && (t.style.display = "block")
    }
  }

  hideChildren(e) {
    e.expanded = !1, this.setExpandedState(e), this.dispatchEvent(new CustomEvent("typo3:svg-tree:expand-toggle", {detail: {node: e}}))
  }

  showChildren(e) {
    e.expanded = !0, this.setExpandedState(e), this.dispatchEvent(new CustomEvent("typo3:svg-tree:expand-toggle", {detail: {node: e}}))
  }

  setExpandedState(e) {
    const t = this.getElementFromNode(e);
    t && (e.hasChildren ? t.setAttribute("aria-expanded", e.expanded ? "true" : "false") : t.removeAttribute("aria-expanded"))
  }

  refreshTree() {
    this.loadData()
  }

  refreshOrFilterTree() {
    "" !== this.searchTerm ? this.filter(this.searchTerm) : this.refreshTree()
  }

  prepareDataForVisibleNodes() {
    const e = {};
    this.nodes.forEach(((t, s) => {
      t.expanded || (e[s] = !0)
    })), this.data.nodes = this.nodes.filter((t => !0 !== t.hidden && !t.parents.some((t => Boolean(e[t]))))), this.data.links = [];
    let t = 0;
    this.data.nodes.forEach(((e, s) => {
      e.x = e.depth * this.settings.indentWidth, e.readableRootline && (t += this.settings.nodeHeight), e.y = s * this.settings.nodeHeight + t, void 0 !== e.parents[0] && this.data.links.push({
        source: this.nodes[e.parents[0]],
        target: e
      }), this.settings.showIcons && (this.fetchIcon(e.icon), this.fetchIcon(e.overlayIcon))
    })), this.svg.attr("height", this.data.nodes.length * this.settings.nodeHeight + this.settings.nodeHeight / 2 + t)
  }

  fetchIcon(e, t = !0) {
    e && (e in this.icons || (this.icons[e] = {
      identifier: e,
      icon: null
    }, Icons.getIcon(e, Icons.sizes.small, null, null, MarkupIdentifiers.inline).then((s => {
      const i = s.match(/<svg[\s\S]*<\/svg>/i);
      if (i) {
        const t = document.createRange().createContextualFragment(i[0]);
        this.icons[e].icon = t.firstElementChild
      }
      t && this.updateVisibleNodes()
    }))))
  }

  updateVisibleNodes() {
    const e = Math.ceil(this.viewportHeight / this.settings.nodeHeight + 1),
      t = Math.floor(Math.max(this.scrollTop - 2 * this.settings.nodeHeight, 0) / this.settings.nodeHeight),
      s = this.data.nodes.slice(t, t + e), i = this.querySelector('[tabindex="0"]'), o = s.find((e => e.focused)),
      n = s.find((e => e.checked));
    let r = this.nodesContainer.selectAll(".node").data(s, (e => e.stateIdentifier));
    const a = this.nodesBgContainer.selectAll(".node-bg").data(s, (e => e.stateIdentifier)),
      d = this.nodesActionsContainer.selectAll(".node-action").data(s, (e => e.stateIdentifier));
    r.exit().remove(), a.exit().remove(), d.exit().remove(), this.updateNodeActions(d);
    const l = this.updateNodeBgClass(a);
    l.attr("class", ((e, t) => this.getNodeBgClass(e, t, l))).attr("style", (e => e.backgroundColor ? "fill: " + e.backgroundColor + ";" : "")), this.updateLinks(), r = this.enterSvgElements(r), r.attr("tabindex", ((e, t) => {
      if (void 0 !== o) {
        if (o === e) return "0"
      } else if (void 0 !== n) {
        if (n === e) return "0"
      } else if (null === i) {
        if (0 === t) return "0"
      } else if (d3selection.select(i).datum() === e) return "0";
      return "-1"
    })).attr("transform", this.getNodeTransform)
      .select(".node-name")
      .html((e => this.getNodeLabel(e))), r.select(".node-toggle").attr("class", this.getToggleClass).attr("visibility", this.getToggleVisibility), this.settings.showIcons && (r.select("use.node-icon").attr("xlink:href", this.getIconId), r.select("use.node-icon-overlay").attr("xlink:href", this.getIconOverlayId), r.select("use.node-icon-locked").attr("xlink:href", (e => "#icon-" + (e.locked ? "overlay-backenduser" : ""))))
  }

  updateNodeBgClass(e) {
    let t = this.settings.nodeHeight;
    t -= 1;
    return e.enter().append("rect").merge(e).attr("width", "100%").attr("height", t).attr("data-state-id", this.getNodeStateIdentifier).attr("transform", (e => this.getNodeBackgroundTransform(e, this.settings.indentWidth, this.settings.nodeHeight))).on("mouseover", ((e, t) => this.onMouseOverNode(t))).on("mouseout", ((e, t) => this.onMouseOutOfNode(t))).on("click", ((e, t) => {
      this.selectNode(t, !0), this.focusNode(t), this.updateVisibleNodes()
    })).on("contextmenu", ((e, t) => {
      e.preventDefault(), this.dispatchEvent(new CustomEvent("typo3:svg-tree:node-context", {detail: {node: t}}))
    }))
  }

  getIconId(e) {
    return "#icon-" + e.icon
  }

  getIconOverlayId(e) {
    return "#icon-" + e.overlayIcon
  }

  selectNode(e, t = !0) {
    this.isNodeSelectable(e) && (this.disableSelectedNodes(), this.disableFocusedNodes(), e.checked = !0, e.focused = !0, this.dispatchEvent(new CustomEvent("typo3:svg-tree:node-selected", {
      detail: {
        node: e,
        propagate: t
      }
    })), this.updateVisibleNodes())
  }

  filter(e) {
    "string" == typeof e && (this.searchTerm = e), this.nodesAddPlaceholder(), this.searchTerm && this.settings.filterUrl ? new AjaxRequest(this.settings.filterUrl + "&q=" + this.searchTerm).get({cache: "no-cache"}).then((e => e.resolve())).then((e => {
      const t = Array.isArray(e) ? e : [];
      t.length > 0 && ("" === this.unfilteredNodes && (this.unfilteredNodes = JSON.stringify(this.nodes)), this.replaceData(t)), this.nodesRemovePlaceholder()
    })).catch((e => {
      throw this.errorNotification(e, !1), this.nodesRemovePlaceholder(), e
    })) : this.resetFilter()
  }

  resetFilter() {
    if (this.searchTerm = "", this.unfilteredNodes.length > 0) {
      const e = this.getSelectedNodes()[0];
      if (void 0 === e) return void this.refreshTree();
      this.nodes = JSON.parse(this.unfilteredNodes), this.unfilteredNodes = "";
      const t = this.getNodeByIdentifier(e.stateIdentifier);
      t ? (this.selectNode(t, !1), this.focusNode(t), this.nodesRemovePlaceholder()) : this.refreshTree()
    } else this.refreshTree();
    this.prepareDataForVisibleNodes(), this.updateVisibleNodes()
  }

  errorNotification(e = null, t = !1) {
    if (!this.muteErrorNotifications) {
      if (Array.isArray(e)) e.forEach((e => {
        Notification.error(e.title, e.message)
      })); else {
        let t = this.networkErrorTitle;
        e && e.target && (e.target.status || e.target.statusText) && (t += " - " + (e.target.status || "") + " " + (e.target.statusText || "")), Notification.error(t, this.networkErrorMessage)
      }
      t && this.loadData()
    }
  }

  connectedCallback() {
    super.connectedCallback(), this.addEventListener("resize", this.updateViewRequested), this.addEventListener("scroll", this.updateViewRequested), this.addEventListener("svg-tree:visible", this.updateViewRequested), window.addEventListener("resize", this.windowResized)
  }

  disconnectedCallback() {
    this.removeEventListener("resize", this.updateViewRequested), this.removeEventListener("scroll", this.updateViewRequested), this.removeEventListener("svg-tree:visible", this.updateViewRequested), window.removeEventListener("resize", this.windowResized), super.disconnectedCallback()
  }

  getSelectedNodes() {
    return this.nodes.filter((e => e.checked))
  }

  getFocusedNodes() {
    return this.nodes.filter((e => e.focused))
  }

  disableFocusedNodes() {
    this.getFocusedNodes().forEach((e => {
      !0 === e.focused && (e.focused = !1)
    }))
  }

  createRenderRoot() {
    return this
  }

  render() {
    return html`
      <div class="node-loader">
        <typo3-backend-icon identifier="spinner-circle" size="small"></typo3-backend-icon>
      </div>
      <svg version="1.1"
           direction="ltr"
           width="100%"
           @mouseover=${()=>this.isOverSvg=!0}
           @mouseout=${()=>this.isOverSvg=!1}
           @keydown=${e=>this.handleKeyboardInteraction(e)}>
        <g class="nodes-wrapper" transform="translate(${this.settings.indentWidth/2},${this.settings.nodeHeight/2})">
          <g class="links"></g>
          <g class="nodes-bg"></g>
          <g class="nodes" role="tree"></g>
          <g class="nodes-actions"></g>
        </g>
        <defs></defs>
      </svg>
    `
  }

  firstUpdated() {
    this.svg = d3selection.select(this.querySelector("svg")), this.container = d3selection.select(this.querySelector(".nodes-wrapper")).attr("transform", "translate(" + this.settings.indentWidth / 2 + "," + this.settings.nodeHeight / 2 + ")"), this.nodesBgContainer = d3selection.select(this.querySelector(".nodes-bg")), this.nodesActionsContainer = d3selection.select(this.querySelector(".nodes-actions")), this.linksContainer = d3selection.select(this.querySelector(".links")), this.nodesContainer = d3selection.select(this.querySelector(".nodes")), this.doSetup(this.setup || {}), this.updateView()
  }

  updateViewRequested() {
    this.updateView()
  }

  updateView() {
    this.updateScrollPosition(), this.updateVisibleNodes(), this.settings.actions && this.settings.actions.length && this.nodesActionsContainer.attr("transform", "translate(" + (this.querySelector("svg").clientWidth - 16 - 16 * this.settings.actions.length) + ",0)")
  }

  disableSelectedNodes() {
    this.getSelectedNodes().forEach((e => {
      !0 === e.checked && (e.checked = !1)
    }))
  }

  updateNodeActions(e) {
    return this.settings.actions && this.settings.actions.length ? (this.nodesActionsContainer.selectAll(".node-action").selectChildren().remove(), e.enter().append("g").merge(e).attr("class", "node-action").on("mouseover", ((e, t) => this.onMouseOverNode(t))).on("mouseout", ((e, t) => this.onMouseOutOfNode(t))).attr("data-state-id", this.getNodeStateIdentifier).attr("transform", (e => this.getNodeActionTransform(e, this.settings.indentWidth, this.settings.nodeHeight)))) : e.enter()
  }

  createIconAreaForAction(e, t) {
    const s = e.append("svg").attr("class", "node-icon-container").attr("height", this.settings.icon.containerSize).attr("width", this.settings.icon.containerSize).attr("x", "0").attr("y", "0");
    s.append("rect").attr("height", this.settings.icon.containerSize).attr("width", this.settings.icon.containerSize).attr("y", "0").attr("x", "0").attr("class", "node-icon-click");
    s.append("svg").attr("height", this.settings.icon.size).attr("width", this.settings.icon.size).attr("y", (this.settings.icon.containerSize - this.settings.icon.size) / 2).attr("x", (this.settings.icon.containerSize - this.settings.icon.size) / 2).attr("class", "node-icon-inner").append("use").attr("class", "node-icon").attr("xlink:href", "#icon-" + t)
  }

  isNodeSelectable(e) {
    return !0
  }

  appendTextElement(e) {
    return e.append("text").attr("dx", this.textPosition).attr("dy", 5).attr("class", "node-name").on("click", ((e, t) => {
      this.selectNode(t, !0), this.focusNode(t), this.updateVisibleNodes()
    }))
  }

  nodesUpdate(e) {
    return (e = e.enter().append("g").attr("class", "node").attr("id", (e => "identifier-" + e.stateIdentifier)).attr("role", "treeitem").attr("aria-owns", (e => e.hasChildren ? "group-identifier-" + e.stateIdentifier : null)).attr("aria-level", this.getNodeDepth).attr("aria-setsize", this.getNodeSetsize).attr("aria-posinset", this.getNodePositionInSet).attr("aria-expanded", (e => e.hasChildren ? e.expanded : null)).attr("transform", this.getNodeTransform).attr("data-state-id", this.getNodeStateIdentifier).attr("title", this.getNodeTitle).on("mouseover", ((e, t) => this.onMouseOverNode(t))).on("mouseout", ((e, t) => this.onMouseOutOfNode(t))).on("contextmenu", ((e, t) => {
      e.preventDefault(), this.dispatchEvent(new CustomEvent("typo3:svg-tree:node-context", {detail: {node: t}}))
    }))).append("text").text((e => e.readableRootline)).attr("class", "node-rootline").attr("dx", 0).attr("dy", this.settings.nodeHeight / 2 * -1).attr("visibility", (e => e.readableRootline ? "visible" : "hidden")), e
  }

  getNodeIdentifier(e) {
    return e.identifier
  }

  getNodeDepth(e) {
    return e.depth
  }

  getNodeSetsize(e) {
    return e.siblingsCount
  }

  getNodePositionInSet(e) {
    return e.siblingsPosition
  }

  getNodeStateIdentifier(e) {
    return e.stateIdentifier
  }

  getNodeLabel(e) {
    let t = (e.prefix || "") + e.name + (e.suffix || "");
    const s = document.createElement("div");
    if (s.textContent = t, t = s.innerHTML, this.searchTerm) {
      const e = new RegExp(this.searchTerm, "gi");
      t = t.replace(e, '<tspan class="node-highlight-text">$&</tspan>')
    }
    return t
  }

  getNodeByIdentifier(e) {
    return this.nodes.find((t => t.stateIdentifier === e))
  }

  getNodeBgClass(e, t, s) {
    let i = "node-bg", o = null, n = null;
    return "object" == typeof s && (o = s.data()[t - 1], n = s.data()[t + 1]), e.checked && (i += " node-selected"), e.focused && (i += " node-focused"), (o && e.depth > o.depth || !o) && (e.firstChild = !0, i += " node-first-child"), (n && e.depth > n.depth || !n) && (e.lastChild = !0, i += " node-last-child"), e.class && (i += " " + e.class), i
  }

  getNodeTitle(e) {
    return e.tip ? e.tip : "uid=" + e.identifier
  }

  getNodeOpacity(e) {
    return 1;
  }

  getToggleVisibility(e) {
    return e.hasChildren ? "visible" : "hidden"
  }

  getToggleClass(e) {
    return "node-toggle node-toggle--" + (e.expanded ? "expanded" : "collapsed") + " chevron " + (e.expanded ? "expanded" : "collapsed")
  }

  getLinkPath(e) {
    const t = e.target.x, s = e.target.y, i = [];
    return i.push("M" + e.source.x + " " + e.source.y), i.push("V" + s), e.target.hasChildren ? i.push("H" + (t - 2)) : i.push("H" + (t + this.settings.indentWidth / 4 - 2)), i.join(" ")
  }

  getNodeTransform(e) {
    return "translate(" + (e.x || 0) + "," + (e.y || 0) + ")"
  }

  getNodeBackgroundTransform(e, t, s) {
    const i = t / 2 * -1;
    let o = (e.y || 0) - s / 2;
    return o += .5, "translate(" + i + ", " + o + ")"
  }

  getNodeActionTransform(e, t, s) {
    const i = t / 2 * -1;
    let o = (e.y || 0) - s / 2;
    return o += .5, o += (s - this.settings.icon.containerSize) / 2, "translate(" + i + ", " + o + ")"
  }

  clickOnIcon(e) {
    this.dispatchEvent(new CustomEvent("typo3:svg-tree:node-context", {detail: {node: e}}))
  }

  handleNodeToggle(e) {
    e.expanded ? this.hideChildren(e) : this.showChildren(e), this.prepareDataForVisibleNodes(), this.updateVisibleNodes()
  }

  enterSvgElements(e) {
    if (this.settings.showIcons) {
      const e = Object.values(this.icons).filter((e => "" !== e.icon && null !== e.icon)),
        t = this.iconsContainer.selectAll(".icon-def").data(e, (e => e.identifier));
      t.exit().remove(), t.enter().append("g").attr("class", "icon-def").attr("id", (e => "icon-" + e.identifier)).append((e => {
        if (e.icon instanceof SVGElement) return e.icon;
        const t = "<svg>" + e.icon + "</svg>";
        return (new DOMParser).parseFromString(t, "image/svg+xml").documentElement.firstChild
      }))
    }
    const t = this.nodesUpdate(e),
      s = t.append("svg").attr("class", "node-toggle").attr("y", this.settings.icon.size / 2 * -1).attr("x", this.settings.icon.size / 2 * -1).attr("visibility", this.getToggleVisibility).attr("height", this.settings.icon.size).attr("width", this.settings.icon.size).on("click", ((e, t) => this.handleNodeToggle(t)));
    if (s.append("use").attr("class", "node-toggle-icon").attr("href", "#icon-actions-chevron-right"), s.append("rect").attr("class", "node-toggle-spacer").attr("height", this.settings.icon.size).attr("width", this.settings.icon.size).attr("fill", "transparent"), this.settings.showIcons) {
      const e = t.append("svg").attr("class", "node-icon-container").attr("height", "20").attr("width", "20").attr("x", "6").attr("y", "-10").on("click", ((e, t) => {
        e.preventDefault(), this.clickOnIcon(t)
      }));
      e.append("rect").style("opacity", 0).attr("width", "20").attr("height", "20").attr("y", "0").attr("x", "0").attr("class", "node-icon-click");
      const s = e.append("svg").attr("height", "16").attr("width", "16").attr("y", "2").attr("x", "2").attr("class", "node-icon-inner");
      s.append("use").attr("class", "node-icon").attr("data-uid", this.getNodeIdentifier);
      s.append("svg").attr("height", "11").attr("width", "11").attr("y", "5").attr("x", "5").append("use").attr("class", "node-icon-overlay");
      s.append("svg").attr("height", "11").attr("width", "11").attr("y", "5").attr("x", "5").append("use").attr("class", "node-icon-locked")
    }
    return t.append("title").text(this.getNodeTitle), this.appendTextElement(t), e.merge(t)
  }

  onMouseOverNode(e) {
    e.isOver = !0, this.hoveredNode = e;
    const t = this.svg.select('.nodes-bg .node-bg[data-state-id="' + e.stateIdentifier + '"]');
    t.size() && t.classed("node-over", !0);
    const s = this.nodesActionsContainer.select('.node-action[data-state-id="' + e.stateIdentifier + '"]');
    s.size() && (s.classed("node-action-over", !0), s.attr("fill", t.style("fill")))
  }

  onMouseOutOfNode(e) {
    e.isOver = !1, this.hoveredNode = null;
    const t = this.svg.select('.nodes-bg .node-bg[data-state-id="' + e.stateIdentifier + '"]');
    t.size() && t.classed("node-over node-alert", !1);
    const s = this.nodesActionsContainer.select('.node-action[data-state-id="' + e.stateIdentifier + '"]');
    s.size() && s.classed("node-action-over", !1)
  }

  updateScrollPosition() {
    this.viewportHeight = this.getBoundingClientRect().height, this.scrollBottom = this.scrollTop + this.viewportHeight + this.viewportHeight / 2
  }

  handleKeyboardInteraction(e) {
    const t = e.target, s = d3selection.select(t).datum();
    if (-1 === [KeyTypes.ENTER, KeyTypes.SPACE, KeyTypes.END, KeyTypes.HOME, KeyTypes.LEFT, KeyTypes.UP, KeyTypes.RIGHT, KeyTypes.DOWN].indexOf(e.keyCode)) return;
    e.preventDefault();
    const i = t.parentNode;
    switch (e.keyCode) {
      case KeyTypes.END:
        this.scrollTop = this.lastElementChild.getBoundingClientRect().height + this.settings.nodeHeight - this.viewportHeight, i.scrollIntoView({
          behavior: "smooth",
          block: "end"
        }), this.focusNode(this.getNodeFromElement(i.lastElementChild)), this.updateVisibleNodes();
        break;
      case KeyTypes.HOME:
        this.scrollTo({
          top: this.nodes[0].y,
          behavior: "smooth"
        }), this.prepareDataForVisibleNodes(), this.focusNode(this.getNodeFromElement(i.firstElementChild)), this.updateVisibleNodes();
        break;
      case KeyTypes.LEFT:
        if (s.expanded) s.hasChildren && (this.hideChildren(s), this.prepareDataForVisibleNodes(), this.updateVisibleNodes()); else if (s.parents.length > 0) {
          const e = this.nodes[s.parents[0]];
          this.scrollNodeIntoVisibleArea(e, "up"), this.focusNode(e), this.updateVisibleNodes()
        }
        break;
      case KeyTypes.UP:
        this.scrollNodeIntoVisibleArea(s, "up"), t.previousSibling && (this.focusNode(this.getNodeFromElement(t.previousSibling)), this.updateVisibleNodes());
        break;
      case KeyTypes.RIGHT:
        s.expanded ? (this.scrollNodeIntoVisibleArea(s, "down"), this.focusNode(this.getNodeFromElement(t.nextSibling)), this.updateVisibleNodes()) : s.hasChildren && (this.showChildren(s), this.prepareDataForVisibleNodes(), this.focusNode(this.getNodeFromElement(t)), this.updateVisibleNodes());
        break;
      case KeyTypes.DOWN:
        this.scrollNodeIntoVisibleArea(s, "down"), t.nextSibling && (this.focusNode(this.getNodeFromElement(t.nextSibling)), this.updateVisibleNodes());
        break;
      case KeyTypes.ENTER:
      case KeyTypes.SPACE:
        this.selectNode(s, !0), this.focusNode(s)
    }
  }

  scrollNodeIntoVisibleArea(e, t = "up") {
    let s = this.scrollTop;
    if ("up" === t && s > e.y - this.settings.nodeHeight) s = e.y - this.settings.nodeHeight; else {
      if (!("down" === t && s + this.viewportHeight <= e.y + 3 * this.settings.nodeHeight)) return;
      s += this.settings.nodeHeight
    }
    this.scrollTo({top: s, behavior: "smooth"}), this.updateVisibleNodes()
  }

  updateLinks() {
    const e = this.data.links.filter((e => e.source.y <= this.scrollBottom && e.target.y >= this.scrollTop - this.settings.nodeHeight)).map((e => (e.source.owns = e.source.owns || [], e.source.owns.push("identifier-" + e.target.stateIdentifier), e))),
      t = this.linksContainer.selectAll(".link").data(e);
    t.exit().remove(), t.enter().append("path").attr("class", "link").attr("id", this.getGroupIdentifier).attr("role", (e => 1 === e.target.siblingsPosition && e.source.owns.length > 0 ? "group" : null)).attr("aria-owns", (e => 1 === e.target.siblingsPosition && e.source.owns.length > 0 ? e.source.owns.join(" ") : null)).merge(t).attr("d", (e => this.getLinkPath(e)))
  }

  getGroupIdentifier(e) {
    return 1 === e.target.siblingsPosition ? "group-identifier-" + e.source.stateIdentifier : null
  }

  registerUnloadHandler() {
    try {
      if (!window.frameElement) return;
      window.addEventListener("pagehide", (() => this.muteErrorNotifications = !0), {once: !0})
    } catch (e) {
      console.error("Failed to check the existence of window.frameElement – using a foreign origin?")
    }
  }
}

__decorate([property({type: Object})], SvgTree.prototype, "setup", void 0), __decorate([state()], SvgTree.prototype, "settings", void 0);
let Toolbar = class extends LitElement {
  constructor() {
    super(...arguments), this.tree = null, this.settings = {searchInput: ".search-input", filterTimeout: 450}
  }

  createRenderRoot() {
    return this
  }

  firstUpdated() {
    const e = this.querySelector(this.settings.searchInput);
    e && new DebounceEvent("input", (e => {
      const t = e.target;
      this.tree.filter(t.value.trim())
    }), this.settings.filterTimeout).bindTo(e)
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

  refreshTree() {
    this.tree.refreshOrFilterTree()
  }

  collapseAll(e) {
    e.preventDefault(), this.tree.nodes.forEach((e => {
      e.parentsStateIdentifier.length && this.tree.hideChildren(e)
    })), this.tree.prepareDataForVisibleNodes(), this.tree.updateVisibleNodes()
  }
};
__decorate([property({type: SvgTree})], Toolbar.prototype, "tree", void 0), Toolbar = __decorate([customElement("typo3-backend-tree-toolbar")], Toolbar);
export {Toolbar};
