package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.AstNode;

public class ObjectClass extends FinalElement {

	public String type = null;
	public String description = "";
	public boolean no_name_check = false;
	public boolean skip = false;
	
	public ObjectClass(String file, String type, AstNode node, Node... docs) {
		super(new Location(file, node));
		this.type = type;
		parse_doc(node, docs);
	}
	public ObjectClass(String type) {
		super(new Location());
		this.type = type;
	}
	public ObjectClass(String file, String type, AstNode node, String description) {
		super(new Location(file, node));
		this.type = type;
		this.description = description;
	}
	private void parse_doc(AstNode node, Node... docs) {
		JSDoc doc = new JSDoc(node, docs);
		if (doc.hasTag("no_doc")) {
			skip = true;
			return;
		}
		this.description = doc.description;
		for (JSDoc.Tag tag : doc.tags) {
			if (tag.name.equals("no_name_check"))
				this.no_name_check = true;
			else
				error("Not supported tag for ObjectClass: "+tag.name);
		}
	}
	
	@Override
	public boolean skip() {
		return skip;
	}
	@Override
	public String getType() {
		return type;
	}
	@Override
	public void setType(String type) {
		this.type = type;
	}
	@Override
	public String getDescription() {
		return description;
	}
	@Override
	public void setDescription(String doc) {
		description = doc;
	}
	
	@Override
	public String generate(String indent) {
		return "new JSDoc_Value(\""+this.type+"\",\""+this.description.replace("\\", "\\\\").replace("\"", "\\\"")+"\","+location.generate()+","+(no_name_check ? "true" : "false")+","+location.generate()+","+(skip ? "true" : "false")+")";
	}
	
}
