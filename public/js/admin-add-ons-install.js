(self.webpackChunk=self.webpackChunk||[]).push([[3132],{32119:(e,t,r)=>{"use strict";r.r(t),r.d(t,{default:()=>T});var n=r(87757),o=r.n(n),a=r(9669),s=r.n(a),i=r(50175),c=r.n(i),d=r(18623);function l(e,t,r,n,o,a,s){try{var i=e[a](s),c=i.value}catch(e){return void r(e)}i.done?t(c):Promise.resolve(c).then(n,o)}function u(e){return function(){var t=this,r=arguments;return new Promise((function(n,o){var a=e.apply(t,r);function s(e){l(a,n,o,s,i,"next",e)}function i(e){l(a,n,o,s,i,"throw",e)}s(void 0)}))}}const f={components:{Preloader:r(69010).Z},mixins:[d.Z],middleware:["auth","verified","2fa_passed","admin"],props:["id"],metaInfo:function(){return{title:this.$t("Install {0} add-on",[this.id])}},data:function(){return{data:null,form:new(c())({code:null})}},created:function(){var e=this;return u(o().mark((function t(){var r,n;return o().wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,s().get("/api/admin/add-ons/".concat(e.id));case 2:r=t.sent,n=r.data,e.data=n,e.form.code=n.code;case 6:case"end":return t.stop()}}),t)})))()},methods:{install:function(){var e=this;return u(o().mark((function t(){var r,n;return o().wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,e.form.post("/api/admin/add-ons/".concat(e.id,"/install"));case 2:r=t.sent,n=r.data,e.$store.dispatch("message/"+(n.success?"success":"error"),{text:n.message}),n.success&&e.$router.push({name:"admin.add-ons.index"});case 6:case"end":return t.stop()}}),t)})))()}}};var m=r(51900),v=r(43453),p=r.n(v),h=r(4330),b=r(43776),V=r(5255),x=r(17024),w=r(66530),Z=r(83240),k=r(6571),_=r(57894),y=r(55515),g=r(54933),I=r(40961),$=r(73845),C=(0,m.Z)(f,(function(){var e=this,t=e.$createElement,r=e._self._c||t;return r("v-container",[r("v-row",{attrs:{align:"center",justify:"center"}},[r("v-col",{attrs:{cols:"12",md:"6"}},[r("v-card",[r("v-toolbar",[r("v-btn",{attrs:{icon:""},on:{click:function(t){return e.$router.go(-1)}}},[r("v-icon",[e._v("mdi-arrow-left")])],1),e._v(" "),r("v-toolbar-title",[e._v("\n            "+e._s(e.$t("Install {0} add-on",[e.id]))+"\n          ")]),e._v(" "),r("preloader",{attrs:{active:!e.data}})],1),e._v(" "),r("v-card-text",[r("v-form",{on:{submit:function(t){return t.preventDefault(),e.install(t)}},model:{value:e.formIsValid,callback:function(t){e.formIsValid=t},expression:"formIsValid"}},[r("v-text-field",{attrs:{label:e.$t("Purchase code"),disabled:e.form.busy,rules:[e.validationRequired],error:e.form.errors.has("code"),"error-messages":e.form.errors.get("code"),outlined:""},on:{keydown:function(t){return e.clearFormErrors(t,"code")}},model:{value:e.form.code,callback:function(t){e.$set(e.form,"code",t)},expression:"form.code"}}),e._v(" "),r("v-skeleton-loader",{attrs:{type:"button",loading:!e.data}},[r("v-btn",{attrs:{type:"submit",color:"primary",disabled:!e.formIsValid||e.form.busy,loading:e.form.busy}},[e._v("\n                "+e._s(e.$t("Install"))+"\n              ")])],1)],1)],1)],1)],1)],1)],1)}),[],!1,null,null,null);const T=C.exports;p()(C,{VBtn:h.Z,VCard:b.Z,VCardText:V.ZB,VCol:x.Z,VContainer:w.Z,VForm:Z.Z,VIcon:k.Z,VRow:_.Z,VSkeletonLoader:y.Z,VTextField:g.Z,VToolbar:I.Z,VToolbarTitle:$.qW})}}]);
//# sourceMappingURL=admin-add-ons-install.js.map